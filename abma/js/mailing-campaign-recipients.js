(function () {
  var csrfToken = $('meta[name="_csrf"]').attr('content');
  var campaignId = null;
  var currentListId = null;
  var lastClickedIndex = null; // لدعم التحديد السريع بالنطاق عبر Shift+Click

  function apiPost(payload) {
    return $.ajax({
      type: 'POST',
      url: 'api/mailing',
      headers: { '_csrf': csrfToken },
      data: JSON.stringify(payload),
      dataType: 'json'
    });
  }

  function toast(icon, title) {
    Swal.fire({
      icon: icon,
      title: title,
      toast: true,
      position: 'top-start',
      showConfirmButton: false,
      timer: 3500,
      timerProgressBar: true
    });
  }

  function escapeHtml(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function updateSelectedCount() {
    var checked = $('.pick-contact-cb:checked').length;
    $('#pickSelectedCount').text('تم تحديد ' + checked);
    $('#pickSubmitCount').text(checked);
  }

  // ===== إضافة قائمة للحملة =====
  $('#addListForm').on('submit', function (e) {
    e.preventDefault();
    var f = Object.fromEntries(new FormData(this).entries());
    if (!f.list_id) return;
    apiPost({ action: 'add_campaign_list', campaign_id: f.campaign_id, list_id: f.list_id })
      .done(function (res) {
        if (res.status) {
          toast('success', res.message);
          setTimeout(function () { location.reload(); }, 600);
        } else {
          Swal.fire({ icon: 'error', title: res.message || 'تعذّرت الإضافة' });
        }
      })
      .fail(function () {
        Swal.fire({ icon: 'error', title: 'تعذّر الاتصال بالخادم' });
      });
  });

  // ===== إزالة قائمة من الحملة =====
  $(document).on('click', '.remove-campaign-list', function () {
    var listId = $(this).data('list-id');
    var name = $(this).data('name');
    var cId = $('#addListForm input[name="campaign_id"]').val() || $('#startSendBtn').data('campaign-id');

    Swal.fire({
      title: 'إزالة "' + name + '" من الحملة؟',
      text: 'المستلمون الذين أُرسل لهم رسالة بالفعل يبقون في السجل، ويُزال فقط من لم يُرسل له بعد.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#FF0000',
      cancelButtonColor: '#9A9A9A',
      confirmButtonText: 'نعم، إزالة',
      cancelButtonText: 'تراجع'
    }).then(function (result) {
      if (!result.isConfirmed) return;
      apiPost({ action: 'remove_campaign_list', campaign_id: cId, list_id: listId })
        .done(function (res) {
          toast(res.status ? 'success' : 'error', res.message);
          if (res.status) setTimeout(function () { location.reload(); }, 600);
        })
        .fail(function () {
          Swal.fire({ icon: 'error', title: 'تعذّر الاتصال بالخادم' });
        });
    });
  });

  // ===== اختيار مستلمين من قائمة معيّنة =====
  $(document).on('click', '.pick-recipients-btn', function () {
    currentListId = $(this).data('list-id');
    campaignId = $('#startSendBtn').data('campaign-id');
    lastClickedIndex = null;

    $('#pickListName').text($(this).data('list-name'));
    $('#pickLoading').show();
    $('#pickEmptyState').hide();
    $('#pickContent').hide();
    $('#pickContactsList').empty();
    updateSelectedCount();

    var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('pickRecipientsModal'));
    modal.show();

    apiPost({ action: 'get_list_contacts_for_campaign', campaign_id: campaignId, list_id: currentListId })
      .done(function (res) {
        $('#pickLoading').hide();
        if (!res.status) {
          Swal.fire({ icon: 'error', title: res.message || 'تعذّر التحميل' });
          return;
        }
        if (!res.contacts.length) {
          $('#pickEmptyState').show();
          return;
        }
        var $list = $('#pickContactsList');
        res.contacts.forEach(function (c) {
          $list.append(
            '<div class="form-check">' +
            '<input class="form-check-input pick-contact-cb" type="checkbox" value="' + c.id + '" id="pc' + c.id + '" checked>' +
            '<label class="form-check-label" for="pc' + c.id + '">' + escapeHtml(c.email) + '</label>' +
            '</div>'
          );
        });
        $('#pickSelectAll').prop('checked', true);
        $('#pickContent').show();
        updateSelectedCount();
      })
      .fail(function () {
        $('#pickLoading').hide();
        Swal.fire({ icon: 'error', title: 'تعذّر الاتصال بالخادم' });
      });
  });

  $('#pickSelectAll').on('change', function () {
    $('.pick-contact-cb').prop('checked', $(this).is(':checked'));
    updateSelectedCount();
  });

  // النقر مع الضغط على Shift يحدّد (أو يلغي تحديد) كل ما بين آخر مربع تم النقر
  // عليه والمربع الحالي دفعة واحدة — تحديد سريع لنطاق كبير من جهات الاتصال
  $(document).on('click', '.pick-contact-cb', function (e) {
    var $checkboxes = $('.pick-contact-cb');
    var currentIndex = $checkboxes.index(this);
    var isChecked = $(this).is(':checked');

    if (e.shiftKey && lastClickedIndex !== null) {
      var start = Math.min(lastClickedIndex, currentIndex);
      var end = Math.max(lastClickedIndex, currentIndex);
      $checkboxes.slice(start, end + 1).prop('checked', isChecked);
    }

    lastClickedIndex = currentIndex;

    var total = $checkboxes.length;
    var checked = $checkboxes.filter(':checked').length;
    $('#pickSelectAll').prop('checked', total === checked);
    updateSelectedCount();
  });

  $('#pickSubmitBtn').on('click', function () {
    var ids = $('.pick-contact-cb:checked').map(function () { return $(this).val(); }).get();
    if (!ids.length) {
      Swal.fire({ icon: 'warning', title: 'اختر جهة اتصال واحدة على الأقل' });
      return;
    }
    var $btn = $(this).prop('disabled', true);
    apiPost({ action: 'add_recipients', campaign_id: campaignId, list_id: currentListId, contact_ids: ids })
      .done(function (res) {
        $btn.prop('disabled', false);
        if (res.status) {
          bootstrap.Modal.getInstance(document.getElementById('pickRecipientsModal')).hide();
          toast('success', res.message);
          setTimeout(function () { location.reload(); }, 600);
        } else {
          Swal.fire({ icon: 'error', title: res.message || 'تعذّرت الإضافة' });
        }
      })
      .fail(function () {
        $btn.prop('disabled', false);
        Swal.fire({ icon: 'error', title: 'تعذّر الاتصال بالخادم' });
      });
  });
})();
