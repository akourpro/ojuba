$(document).on("change", ".addon-toggle", function () {
  var $checkbox = $(this);
  var $card = $checkbox.closest(".addon-card");
  var $iconWrap = $card.find(".addon-icon-wrap");
  var module = $checkbox.data("module");
  var enabled = $checkbox.is(":checked");

  $checkbox.prop("disabled", true);

  $.ajax({
    type: "POST",
    url: "api/addons",
    headers: { '_csrf': $('meta[name="_csrf"]').attr('content') },
    data: JSON.stringify({ action: "toggle", module: module, enabled: enabled }),
    dataType: "json",
    encode: true,
  })
    .done(function (data) {
      if (data.status) {
        $iconWrap
          .toggleClass("bg-label-success", enabled)
          .toggleClass("bg-label-secondary", !enabled);

        Swal.fire({
          icon: "success",
          title: data.message,
          toast: true,
          position: "top-start",
          showConfirmButton: false,
          timer: 2500,
          timerProgressBar: true,
          didOpen: (toast) => {
            toast.addEventListener("mouseenter", Swal.stopTimer);
            toast.addEventListener("mouseleave", Swal.resumeTimer);
          },
        });
      } else {
        // فشل الحفظ: نعيد المفتاح لحالته السابقة
        $checkbox.prop("checked", !enabled);
        Swal.fire({
          icon: "error",
          title: data.message,
          toast: true,
          position: "top-start",
          showConfirmButton: false,
          timer: 3000,
          timerProgressBar: true,
        });
      }
    })
    .fail(function () {
      $checkbox.prop("checked", !enabled);
      Swal.fire({
        icon: "error",
        title: "تعذّر الاتصال بالخادم",
        toast: true,
        position: "top-start",
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
      });
    })
    .always(function () {
      $checkbox.prop("disabled", false);
    });
});
