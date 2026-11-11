// Keep previously issued root-fragment recovery links working after introducing the product page.
const recovery = new URLSearchParams(location.hash.slice(1));
if (recovery.has('reset_token')) {
    location.replace(document.querySelector('meta[name="workspace-url"]').content + location.hash);
}
