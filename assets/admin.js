jQuery(function ($) {
    $('.cx-add-tier').on('click', function () {
        const box = $(this).closest('.cx-tier-box');
        const tbody = box.find('tbody');
        const index = $('.cx-tier-box').index(box);

        const row = $('#cx-tier-row-template').clone();

        row.removeAttr('id');

        row.find('input[data-name="qty"]').attr(
            'name',
            'tiers[' + index + '][qty][]'
        );

        row.find('input[data-name="price"]').attr(
            'name',
            'tiers[' + index + '][price][]'
        );

        tbody.append(row);
    });

    $(document).on('click', '.cx-remove-tier', function (e) {
        e.preventDefault();

        $(this).closest('tr').remove();
    });
});