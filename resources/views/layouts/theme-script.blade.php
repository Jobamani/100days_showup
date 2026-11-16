<script>
    (function () {
        try {
            var t = localStorage.getItem('theme') || 'system';
            var dark = t === 'dark' || (t === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', dark);
        } catch (e) {}
    })();
</script>