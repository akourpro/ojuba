$("#themeImportForm").on("submit", function (e) {
  e.preventDefault();

  const fileInput = document.getElementById("themeZipInput");
  if (!fileInput.files || !fileInput.files[0]) {
    Swal.fire({ icon: "warning", title: "اختر ملف zip أولاً" });
    return;
  }

  const formData = new FormData();
  formData.append("action", "upload");
  formData.append("zipfile", fileInput.files[0]);

  Swal.fire({
    title: "جاري رفع القالب والتحقق منه...",
    text: "قد يستغرق هذا بضع ثوانٍ حسب حجم الملف",
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading(),
  });

  $.ajax({
    type: "POST",
    url: "api/templates_import",
    headers: { "_csrf": $('meta[name="_csrf"]').attr("content") },
    data: formData,
    processData: false,
    contentType: false,
  })
    .done(function (res) {
      if (!res.status) {
        Swal.fire({ icon: "error", title: "تعذّر الاستيراد", text: res.message });
        return;
      }

      if (res.conflict) {
        Swal.fire({
          icon: "warning",
          title: "قالب بنفس الاسم موجود مسبقاً",
          text: res.message,
          showCancelButton: true,
          confirmButtonText: "نعم، استبدله",
          cancelButtonText: "إلغاء",
          confirmButtonColor: "#FF0000",
        }).then((result) => {
          if (result.isConfirmed) {
            confirmImport(res.staging_token, res.slug, true);
          } else {
            confirmImport(res.staging_token, res.slug, false);
          }
        });
        return;
      }

      showImportSuccess(res);
    })
    .fail(function () {
      Swal.fire({ icon: "error", title: "حدث خطأ أثناء رفع الملف" });
    });
});

function confirmImport(token, slug, confirm) {
  Swal.fire({
    title: confirm ? "جاري الاستبدال..." : "جاري الإلغاء...",
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading(),
  });

  $.ajax({
    type: "POST",
    url: "api/templates_import",
    headers: { "_csrf": $('meta[name="_csrf"]').attr("content") },
    data: { action: "confirm", staging_token: token, slug: slug, confirm: confirm ? "1" : "0" },
    dataType: "json",
  })
    .done(function (res) {
      if (!res.status) {
        Swal.fire({ icon: "error", title: "تعذّر إتمام العملية", text: res.message });
        return;
      }
      if (res.cancelled) {
        Swal.fire({ icon: "info", title: "تم إلغاء الاستيراد", toast: true, position: "top-start", showConfirmButton: false, timer: 2500 });
        return;
      }
      showImportSuccess(res);
    })
    .fail(function () {
      Swal.fire({ icon: "error", title: "حدث خطأ أثناء إتمام العملية" });
    });
}

function showImportSuccess(res) {
  let html = "<p>" + res.message + "</p>";
  if (res.warnings && res.warnings.length) {
    html += '<ul style="text-align:right;padding-inline-start:20px;color:#a17a00">';
    res.warnings.forEach((w) => (html += "<li>" + w + "</li>"));
    html += "</ul>";
  }
  Swal.fire({
    icon: "success",
    title: "تم استيراد القالب",
    html: html,
  }).then(() => location.reload());
}
