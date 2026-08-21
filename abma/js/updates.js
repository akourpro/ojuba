$("#checkUpdateBtn").click(function () {
  var btn = $(this);
  btn.prop("disabled", true);
  $.ajax({
    type: "POST",
    url: "api/updates",
    headers: { '_csrf': $('meta[name="_csrf"]').attr('content') },
    data: JSON.stringify({ action: "check" }),
    dataType: "json",
    encode: true,
  }).done(function (data) {
    Swal.fire({
      icon: data.status ? "success" : "error",
      title: data.message,
    }).then(function () {
      location.reload();
    });
  }).fail(function () {
    btn.prop("disabled", false);
    Swal.fire({ icon: "error", title: "تعذّر الاتصال", text: "حدث خطأ غير متوقع أثناء التحقق من التحديثات" });
  });
});

$("#applyUpdateBtn").click(function () {
  var targetVersion = $(this).data("target-version");

  Swal.fire({
    title: "تحديث السكربت إلى الإصدار " + targetVersion + "؟",
    html: "سيتم إنشاء نسخة احتياطية كاملة تلقائياً قبل البدء. ملفات كود القوالب الرسمية ستُستبدَل — أي تعديل يدوي مباشر عليها (خارج لوحة التحكم) سيُفقَد. لا تُغلق الصفحة أثناء التحديث.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#71dd37",
    cancelButtonColor: "#9A9A9A",
    confirmButtonText: "نعم، حدّث الآن",
    cancelButtonText: "تراجع",
  }).then((result) => {
    if (!result.isConfirmed) return;

    Swal.fire({
      title: "جاري تطبيق التحديث...",
      html: "قد يستغرق هذا دقيقة أو أكثر — الرجاء عدم إغلاق هذه الصفحة أو تحديثها.",
      allowOutsideClick: false,
      allowEscapeKey: false,
      showConfirmButton: false,
      didOpen: () => { Swal.showLoading(); },
    });

    $.ajax({
      type: "POST",
      url: "api/updates",
      headers: { '_csrf': $('meta[name="_csrf"]').attr('content') },
      data: JSON.stringify({ action: "apply" }),
      dataType: "json",
      encode: true,
      timeout: 300000,
    }).done(function (data) {
      var stepsHtml = "";
      if (Array.isArray(data.log)) {
        stepsHtml = '<ul style="text-align:right;list-style:none;padding:0;margin:0;">' +
          data.log.map(function (step) {
            var icon = step.ok ? '✅' : '❌';
            return '<li style="margin-bottom:6px;">' + icon + ' ' + step.text + '</li>';
          }).join('') + '</ul>';
      }
      Swal.fire({
        icon: data.status ? "success" : "error",
        title: data.message,
        html: stepsHtml,
      }).then(function () {
        location.reload();
      });
    }).fail(function () {
      Swal.fire({
        icon: "error",
        title: "تعذّر الاتصال أثناء التحديث",
        text: "قد يكون التحديث ما زال يعمل بالخلفية إن كانت العملية طويلة — تحقّق من الإصدار الحالي بعد قليل قبل إعادة المحاولة.",
      });
    });
  });
});
