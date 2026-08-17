var inputField = $(".slug");

  inputField.on("input", function() {
      var text = $(this).val();
      text = text.replace(/\s+/g, "-");
      text = text.replace(/[^a-zA-Z0-9\u0600-\u06FF\-_]/g, "");
      $(this).val(text);
  });

  $('#slug').on('blur', function() {
    let slug = $(this).val().trim();
    let id = $(this).data('id'); // الحصول على معرف المقالة إذا كان موجودًا

    // إذا كان الحقل فارغًا لا ترسل الطلب
    $('button[type="submit"]').attr('disabled', 'disabled'); // تعطيل زر الإرسال
    $('#message').html('');
    if (slug === '') return;

    $.ajax({
        type: "POST",
        url: "api/pages",
        data: JSON.stringify({
            slug: slug,
            id: id, // إرسال معرف المقالة للتحقق من التحديث
            action: "check_slug"
        }),
        contentType: "application/json",
        dataType: "json",
        beforeSend: function() {
            $('#message').html('<span class="text-info">جارٍ التحقق من توفر الرابط...</span>');
        },
    }).done(function(data) {
        if (data.status) {
            $('#message').html('<span class="text-success">' + data.message + '</span>');
            $('button[type="submit"]').removeAttr('disabled'); // تفعيل زر الإرسال
        } else {
            $('#message').html('<span class="text-danger">' + data.message + '</span>');
            $('button[type="submit"]').attr('disabled', 'disabled'); // تعطيل زر الإرسال
        }
    }).fail(function() {
        $('#message').html('<span class="text-warning">حدث خطأ أثناء التحقق. حاول مرة أخرى.</span>');
        $('button[type="submit"]').attr('disabled', 'disabled'); // تعطيل زر الإرسال
    });
 });




$(".delete").click(function () {
  id = $(this).data("id");
  action = $(this).data("action");
  pageName = $(this).data("name");

  Swal.fire({
    title: "هل انت متأكد من حذف الصفحة (" + pageName + ")",
    icon: "error",
    showCancelButton: true,
    confirmButtonColor: "#FF0000",
    cancelButtonColor: "#9A9A9A",
    confirmButtonText: "نعم",
    cancelButtonText: "تراجع",
  }).then((result) => {
    if (result.isConfirmed) {
      $.ajax({
        type: "POST",
        url: "api/pages",
        headers: { '_csrf': $('meta[name="_csrf"]').attr('content') },
        data: JSON.stringify({ id: id, action: action }),
        dataType: "json",
        encode: true,
        beforeSend: function () {
          let timerInterval;
          Swal.fire({
            title: "الرجاء الانتظار ...",
            timer: 5000,
            timerProgressBar: true,
            didOpen: () => {
              Swal.showLoading();
              timerInterval = setInterval(() => {}, 100);
            },
            willClose: () => {
              clearInterval(timerInterval);
            },
          });
        },
      }).done(function (data) {
        if (data.status) {
          Swal.fire({
            icon: "success",
            title: data.message,
            toast: true,
            position: "top-start",
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
              toast.addEventListener("mouseenter", Swal.stopTimer);
              toast.addEventListener("mouseleave", Swal.resumeTimer);
            },
          });
          setTimeout(location.reload.bind(location), 500);
        } else {
          Swal.fire({
            icon: "error",
            title: data.message,
            toast: true,
            position: "top-start",
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
              toast.addEventListener("mouseenter", Swal.stopTimer);
              toast.addEventListener("mouseleave", Swal.resumeTimer);
            },
          });
        }
      });
    }
  });
});
