$("#addNewBtn").click(function () {
    resetForm();
    $("#addEventSidebarLabel").text("إضافة فئة جديدة");
    $("#saveEventBtn").text("إضافة");
});

// تعديل فئة - جلب البيانات من API
$(".edit-btn").click(function () {
    var id = $(this).data("id");
    
    // إرسال طلب للحصول على البيانات
    $.ajax({
        type: "POST",
        url: "api/categories",
        data: JSON.stringify({ 
            id: id,
            action: "get" 
        }),
        dataType: "json",
        contentType: "application/json",
        beforeSend: function () {
            Swal.fire({
                title: "جاري التحميل...",
                timer: 3000,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        },
    }).done(function (data) {
        Swal.close();
        
        if (data.status) {
            // تعبئة الحقول بالبيانات
            $("#category_id").val(data.data.id);
            $("#name").val(data.data.name);
            $("#name_en").val(data.data.name_en);
            
            // تغيير النصوص
            $("#addEventSidebarLabel").text("تعديل الفئة");
            $("#saveEventBtn").text("تحديث");
            
            // عرض السايدبار
            var addEventSidebar = new bootstrap.Offcanvas(document.getElementById('addEventSidebar'));
            addEventSidebar.show();
        } else {
            Swal.fire({
                icon: "error",
                title: data.message || "فشل في تحميل البيانات",
                toast: true,
                position: "top-start",
                showConfirmButton: false,
                timer: 3000
            });
        }
    }).fail(function () {
        Swal.fire({
            icon: "warning",
            title: "فشل في تحميل البيانات، يرجى المحاولة مرة أخرى.",
            toast: true,
            position: "top-start",
            showConfirmButton: false,
            timer: 3000
        });
    });
});

// حفظ (إضافة/تعديل)
$("#saveEventBtn").click(function (e) {
    e.preventDefault();

    var id = $("#category_id").val();
    var name_ar = $("#name").val();
    var name_en = $("#name_en").val();
    
    // تحديد العملية بناءً على وجود ID
    var action = id ? "edit" : "add";

    $.ajax({
        type: "POST",
        url: "api/categories",
        headers: { '_csrf': $('meta[name="_csrf"]').attr('content') },
        data: JSON.stringify({
            id: id,
            action: action,
            name: name_ar,
            name_en: name_en
        }),
        dataType: "json",
        contentType: "application/json",
        beforeSend: function () {
            let timerInterval;
            Swal.fire({
                title: "الرجاء الانتظار ...",
                timerProgressBar: true,
                didOpen: () => {
                    Swal.showLoading();
                },
                willClose: () => {
                    clearInterval(timerInterval);
                },
            });
        },
    }).done(function (data) {
        Swal.close();
        
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
            
            // إغلاق السايدبار بعد نجاح الحفظ
            var addEventSidebar = bootstrap.Offcanvas.getInstance(document.getElementById('addEventSidebar'));
            if (addEventSidebar) {
                addEventSidebar.hide();
            }
            
            setTimeout(location.reload.bind(location), 500);
        } else {
            Swal.fire({
                icon: "error",
                title: data.message || "حدث خطأ أثناء الحفظ",
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
    }).fail(function () {
        Swal.fire({
            icon: "warning",
            title: "فشل في ارسال الطلب، يرجى المحاولة مرة أخرى.",
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
    });
});

// مسح الحقول عند إغلاق السايدبار
$('#addEventSidebar').on('hidden.bs.offcanvas', function () {
    resetForm();
});

// دالة لمسح الحقول
function resetForm() {
    $("#category_id").val("");
    $("#name").val("");
    $("#name_en").val("");
    $("#saveEventBtn").text("إضافة");
}

// حذف فئة
$(".delete").click(function () {
    var id = $(this).data("id");
    var category_name = $(this).data("category_name");

    Swal.fire({
        title: "هل انت متأكد من حذف الفئة: " + category_name,
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
                url: "api/categories",
                headers: { '_csrf': $('meta[name="_csrf"]').attr('content') },
                data: JSON.stringify({
                    id: id,
                    action: "delete"
                }),
                dataType: "json",
                contentType: "application/json",
                beforeSend: function () {
                    Swal.fire({
                        title: "الرجاء الانتظار ...",
                        timerProgressBar: true,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                },
            }).done(function (data) {
                Swal.close();
                
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
            }).fail(function () {
                Swal.fire({
                    icon: "warning",
                    title: "فشل في ارسال الطلب، يرجى المحاولة مرة أخرى.",
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
            });
        }
    });
});