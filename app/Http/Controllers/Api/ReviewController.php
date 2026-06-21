<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Car;
use App\Models\Review;
use App\Models\ServiceRecord;
use App\Notifications\NewReviewReceived;

class ReviewController extends Controller
{
    public function store(StoreReviewRequest $request, Car $car, ServiceRecord $serviceRecord)
    {
        if ($serviceRecord->review) {
            return response()->json([
                'message' => 'You have already reviewed this service record',
            ], 422);
        }

        $review = Review::create([
            'owner_id'          => auth()->id(),
            'mechanic_id'       => $serviceRecord->mechanic_id,
            'service_record_id' => $serviceRecord->id,
            'rating'            => $request->rating,
            'comment'           => $request->comment,
        ]);

        $review->load('mechanic', 'owner');

        $review->mechanic->notify(new NewReviewReceived($review));

        return response()->json([
            'message' => 'Review submitted successfully',
            'review'  => new ReviewResource($review),
        ], 201);
    }

    public function index(Car $car, ServiceRecord $serviceRecord)
    {
        $review = $serviceRecord->review()->with('owner', 'mechanic')->first();

        if (!$review) {
            return response()->json([
                'message' => 'No review found for this service record',
            ], 404);
        }

        return new ReviewResource($review);
    }

    public function mechanicReviews()
    {
        $mechanic = auth()->user();

        if ($mechanic->role !== 'mechanic') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $reviews = $mechanic->reviews()
            ->with('owner', 'serviceRecord')
            ->latest()
            ->paginate(10);

        return response()->json([
            'average_rating' => $mechanic->average_rating,
            'reviews'        => ReviewResource::collection($reviews),
        ]);
    }
}
