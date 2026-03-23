(function ($) {
    function updatePreview($input, color) {
        var target = $input.data('preview');
        if (!target) {
            return;
        }
        var safeColor = color || '#f4f6f9';
        $(target).css('background', safeColor);
    }

    $(function () {
        var $colorFields = $('.syt-color-field');
        if ($colorFields.length) {
            $colorFields.wpColorPicker({
                change: function (event, ui) {
                    updatePreview($(event.target), ui.color.toString());
                },
                clear: function (event) {
                    updatePreview($(event.target), '');
                }
            });

            $colorFields.each(function () {
                updatePreview($(this), $(this).val());
            });
        }

        $('.syt-copy-btn').on('click', function (event) {
            event.preventDefault();
            var target = $(this).data('target');
            var $input = $(target);
            if (!$input.length) {
                return;
            }

            $input[0].select();
            $input[0].setSelectionRange(0, 99999);

            try {
                document.execCommand('copy');
            } catch (err) {
                // Ignore clipboard errors.
            }

            var $status = $(this).closest('.syt-admin-card').find('.syt-copy-status');
            if ($status.length) {
                $status.text('K\u0131sa kod kopyaland\u0131.');
                setTimeout(function () {
                    $status.text('');
                }, 2000);
            }
        });
    });
})(jQuery);
