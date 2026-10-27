<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

@if ($fit ?? false)
<script>
    (function () {
        var fit = document.getElementById('swFit');
        if (!fit) return;

        var mq = window.matchMedia('(min-width: 1200px)');

        function apply() {
            if (!mq.matches) {
                fit.style.width = '100%';
                fit.style.transform = 'none';
                return;
            }

            var vh = window.innerHeight, scale = 1;

            for (var i = 0; i < 3; i++) {
                fit.style.width = (100 / scale) + '%';
                var h = fit.offsetHeight;
                var next = Math.min(vh / h, 1);
                if (next === scale) break;
                scale = next;
            }

            fit.style.width = (100 / scale) + '%';
            fit.style.transform = scale < 1 ? 'scale(' + scale + ')' : 'none';
        }

        window.addEventListener('resize', apply);
        window.addEventListener('load', apply);
        if (document.fonts && document.fonts.ready) document.fonts.ready.then(apply);
        if (mq.addEventListener) mq.addEventListener('change', apply);
        apply();
    })();
</script>
@endif

@stack('scripts')
