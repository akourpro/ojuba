$(".delete").click(function () {
  id = $(this).data("id");
  action = $(this).data("action");
  itemName = $(this).data("name");

  Swal.fire({
    title: "هل انت متأكد من حذف (" + itemName + ")",
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
        url: "api/feeds",
        headers: { '_csrf': $('meta[name="_csrf"]').attr('content') },
        data: JSON.stringify({ id: id, action: action }),
        dataType: "json",
        encode: true,
        beforeSend: function () {
          let timerInterval;
          Swal.fire({
            title: "الرجاء الانتظار ...",
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

// زر "سحب الآن" — يشغّل الاستيراد فوراً لمصدر واحد محدَّد (بدون انتظار الجدولة
// التلقائية)، ثم يعرض ملخّص النتيجة (عدد المقالات المستوردة/سبب الفشل إن وُجد).
$(".pull-now").click(function () {
  var id = $(this).data("id");
  var btn = $(this);

  Swal.fire({
    title: "جاري السحب من المصدر...",
    text: "قد يستغرق هذا بضع ثوانٍ حسب حجم الـfeed",
    timerProgressBar: true,
    allowOutsideClick: false,
    didOpen: () => {
      Swal.showLoading();
    },
  });

  $.ajax({
    type: "POST",
    url: "api/feeds",
    headers: { '_csrf': $('meta[name="_csrf"]').attr('content') },
    data: JSON.stringify({ id: id, action: "pull" }),
    dataType: "json",
    encode: true,
  }).done(function (data) {
    if (data.status) {
      Swal.fire({
        icon: "success",
        title: "تم السحب",
        text: data.message,
      }).then(function () {
        location.reload();
      });
    } else {
      Swal.fire({
        icon: "error",
        title: "فشل السحب",
        text: data.message,
      });
    }
  }).fail(function () {
    Swal.fire({
      icon: "error",
      title: "تعذّر الاتصال",
      text: "حدث خطأ غير متوقع أثناء محاولة السحب",
    });
  });
});
