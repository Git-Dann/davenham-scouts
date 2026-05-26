jQuery(function ($) {
  function updatePreview($input) {
    var $wrap = $input.closest('td');
    var value = $input.val();
    var $preview = $wrap.find('.das-logo-preview');

    if (!value) {
      $preview.remove();
      return;
    }

    if (!$preview.length) {
      $preview = $('<p class="das-logo-preview"><img alt=""></p>');
      $wrap.append($preview);
    }

    $preview.find('img').attr('src', value);
  }

  $('.das-media-open').on('click', function (event) {
    event.preventDefault();

    var $button = $(this);
    var target = $button.data('target');
    var idTarget = $button.data('id-target');
    var $input = $(target);
    var $idInput = $(idTarget);

    var frame = wp.media({
      title: 'Choose admin logo',
      button: {
        text: 'Use this image'
      },
      library: {
        type: ['image']
      },
      multiple: false
    });

    frame.on('select', function () {
      var attachment = frame.state().get('selection').first().toJSON();
      $input.val(attachment.url).trigger('change');
      if ($idInput.length) {
        $idInput.val(attachment.id);
      }
      updatePreview($input);
    });

    frame.open();
  });

  $('.das-media-clear').on('click', function (event) {
    event.preventDefault();

    var target = $(this).data('target');
    var idTarget = $(this).data('id-target');
    var $input = $(target);
    var $idInput = $(idTarget);
    $input.val('').trigger('change');
    if ($idInput.length) {
      $idInput.val('');
    }
    updatePreview($input);
  });

  $('#das_admin_logo_url').on('change', function () {
    updatePreview($(this));
  });

  $('.das-add-custom-link').on('click', function (event) {
    event.preventDefault();

    var $wrap = $(this).closest('.das-custom-links');
    var $rows = $wrap.find('.das-custom-link-rows');
    var template = $('#das-custom-link-template').html();
    var nextIndex = parseInt($wrap.attr('data-next-index'), 10) || 0;

    template = template.replace(/__INDEX__/g, nextIndex);
    $rows.append(template);
    $wrap.attr('data-next-index', nextIndex + 1);
  });

  $(document).on('click', '.das-remove-custom-link', function (event) {
    event.preventDefault();
    $(this).closest('.das-custom-link-row').remove();
  });
});
