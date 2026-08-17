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
        url: "api/branches",
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
