<script>
    document.querySelectorAll('[data-highlight-builder]').forEach(function (builder) {
        var rows = builder.querySelector('[data-highlight-rows]');
        var template = builder.querySelector('[data-highlight-template]');
        var key = builder.dataset.key;
        var next = Array.from(rows.querySelectorAll('input[name]')).reduce(function (max, input) {
        var match = input.name.match(/\[(\d+)\]/);
        return match ? Math.max(max, Number(match[1]) + 1) : max;
    }, 0);

        builder.querySelector('[data-highlight-add]').addEventListener('click', function () {
            rows.insertAdjacentHTML('beforeend', template.innerHTML.replace(/__KEY__/g, key).replace(/__I__/g, String(next++)));
        });
        builder.addEventListener('click', function (event) {
            var button = event.target.closest('[data-highlight-remove]');
            if (button) button.closest('[data-highlight-row]').remove();
        });
    });
</script>
