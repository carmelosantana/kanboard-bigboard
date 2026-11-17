$('body').on('click', '.bbprojectcollapse', function () {
    var project_id = $(this).find('span.collapsible').attr('data-project-id');
    $('#open-' + project_id).hide();
    $('#close-' + project_id).hide();
    $('#wip-' + project_id).show();
    $.ajax($(this).attr('data-url')).done(function (data) {
        if (data.status) {
            $('#close-' + project_id).show();
            $('div.BBcontent[data-project-id="' + project_id + '"]').hide();
        } else {
            $('#open-' + project_id).show();
            $('div.BBcontent[data-project-id="' + project_id + '"]').show();
        }
        $('#wip-' + project_id).hide();
    });
});

$('body').on('click', 'li.collapse_all', function (e) {
    e.preventDefault();
    $('#status_update').html('<span class="alert">update <i class="fa fa-cog fa-spin fa-fw"></i></span>').show();
    $.ajax($(this).attr('data-url')).done(function () {
        $('div.BBcontent').hide();
        $('.open').hide();
        $('.close').show();
        $('#status_update').html('');
    });
});

$('body').on('click', 'li.expand_all', function (e) {
    e.preventDefault();
    $('#status_update').html('<span class="alert">update <i class="fa fa-cog fa-spin fa-fw"></i></span>').show();
    $.ajax($(this).attr('data-url')).done(function () {
        $('div.BBcontent').show();
        $('.open').show();
        $('.close').hide();
        $('#status_update').html('');
    });
});
