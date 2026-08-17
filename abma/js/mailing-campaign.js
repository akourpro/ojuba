(function () {
  var mediaAttachments = []; // [{path, name}]

  function relPathFromUrl(url) {
    var idx = url.indexOf('files/');
    return idx !== -1 ? url.substring(idx) : url;
  }

  function renderChips() {
    var $wrap = $('#attachmentsChips');
    $wrap.empty();
    mediaAttachments.forEach(function (item, i) {
      var $chip = $(
        '<span class="badge bg-label-secondary d-inline-flex align-items-center gap-1 p-2">' +
        '<i class="mdi mdi-paperclip"></i> ' + item.name +
        ' <i class="mdi mdi-close-circle ms-1 remove-attachment-chip" data-index="' + i + '" style="cursor:pointer"></i>' +
        '</span>'
      );
      $wrap.append($chip);
    });
    $('#mediaAttachmentsJson').val(JSON.stringify(mediaAttachments));
  }

  $(document).on('click', '.remove-attachment-chip', function () {
    var idx = $(this).data('index');
    mediaAttachments.splice(idx, 1);
    renderChips();
  });

  $(document).on('media-picker:selected', function (e, item) {
    if (!item.trigger) return;
    var triggerId = item.trigger.id;
    var relPath = relPathFromUrl(item.url);

    if (triggerId === 'templateLibraryBtn') {
      $('#templateMediaPath').val(relPath);
      $('#templateFileInput').val(''); // نفرغ حقل الرفع المباشر حتى لا يتعارض مع الاختيار من المكتبة
      $('#templateSelectedInfo').text('تم اختيار القالب: ' + (item.original_name || item.filename)).show();
    } else if (triggerId === 'attachmentLibraryBtn') {
      mediaAttachments.push({ path: relPath, name: item.original_name || item.filename });
      renderChips();
    }
  });
})();
