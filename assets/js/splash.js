const nextUrl = document.body.dataset.next;
if (nextUrl) {
    window.setTimeout(() => {
        window.location.href = nextUrl;
    }, 2000);
}
