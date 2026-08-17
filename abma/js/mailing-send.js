(function () {
  var csrfToken = $('meta[name="_csrf"]').attr('content');
  var sending = false;
  // فاصل زمني بين كل دفعة والتالية (بالميلي ثانية)، إضافة إلى التأخير بين
  // الرسائل نفسها داخل الدفعة (يُضبط من MAILING_DELAY_MICROSECONDS في
  // abma/api/mailing.php) — كلاهما يقلّل احتمال حظر/تقييد الحساب من مزود
  // الاستضافة عند إرسال أعداد كبيرة من الرسائل دفعة واحدة
  var DELAY_BETWEEN_BATCHES_MS = 2000;

  function sendNextBatch(campaignId) {
    $.ajax({
      type: 'POST',
      url: 'api/mailing',
      headers: { '_csrf': csrfToken },
      data: JSON.stringify({ action: 'send_batch', campaign_id: campaignId }),
      dataType: 'json',
    }).done(function (data) {
      if (!data.status) {
        sending = false;
        $('#startSendBtn').prop('disabled', false).html('<i class="mdi mdi-email-fast-outline"></i> إعادة المحاولة');
        Swal.fire({ icon: 'error', title: data.message || 'حدث خطأ أثناء الإرسال', toast: true, position: 'top-start', showConfirmButton: false, timer: 4000 });
        return;
      }

      var pct = data.total > 0 ? Math.round((data.sent_total / data.total) * 100) : 0;
      $('#sendProgressBar').css('width', pct + '%');
      $('#progressText').text(data.sent_total + ' / ' + data.total);
      $('#sendLog').prepend('<div>تم إرسال ' + data.sent_in_batch + ' رسالة' + (data.failed_in_batch ? '، وفشل ' + data.failed_in_batch : '') + ' (الإجمالي: ' + data.sent_total + '/' + data.total + ')</div>');

      if (data.done) {
        sending = false;
        $('#sendProgressBar').removeClass('progress-bar-striped progress-bar-animated');
        $('#campaignStatusLabel').text('تم الإرسال');
        $('#startSendBtn').prop('disabled', true).html('<i class="mdi mdi-check-circle-outline"></i> تم إرسال الحملة بالكامل');
        Swal.fire({ icon: 'success', title: 'اكتمل إرسال الحملة', toast: true, position: 'top-start', showConfirmButton: false, timer: 3000 });
      } else {
        setTimeout(function () { sendNextBatch(campaignId); }, DELAY_BETWEEN_BATCHES_MS);
      }
    }).fail(function () {
      sending = false;
      $('#startSendBtn').prop('disabled', false).html('<i class="mdi mdi-email-fast-outline"></i> إعادة المحاولة');
      Swal.fire({ icon: 'error', title: 'تعذّر الاتصال بالخادم', toast: true, position: 'top-start', showConfirmButton: false, timer: 4000 });
    });
  }

  $(document).on('click', '#startSendBtn', function () {
    if (sending) return;
    var campaignId = $(this).data('campaign-id');
    sending = true;
    $(this).prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin"></i> جاري الإرسال...');
    $('#sendProgressBar').addClass('progress-bar-striped progress-bar-animated');
    $('#campaignStatusLabel').text('جاري الإرسال');
    $('#sendLog').empty();
    sendNextBatch(campaignId);
  });
})();
