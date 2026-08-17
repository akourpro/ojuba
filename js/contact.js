//
// وقت تحميل النموذج، يُستخدم في الباك اند لرصد الإرسال الفوري المشبوه (بوتات)
var contactFormLoadedAt = Math.floor(Date.now() / 1000);

$("#sendMessage").click(function () {
    action = "contact";
    csrf = $("#csrf").val();
    fname = $("#fname").val();
    lname = $("#lname").val();
    phone = $("#phone").val();
    email = $("#email").val();
    message = $("#message").val();
    website = $("#website").val() || ""; // حقل honeypot، يجب أن يبقى فارغاً

    if (fname && lname && phone && email && message) {
        $.ajax({
            type: "POST",
            url: "api/contact",
            data: JSON.stringify({ action: action, csrf: csrf, fname: fname, lname: lname, phone: phone, email: email, message: message, website: website, loaded_at: contactFormLoadedAt }),
            dataType: "json",
            encode: true,
            beforeSend: function () {
                $("#sendMessage").html(
                    '<i class="fa fa-spinner fa-spin"></i> ' + langu.please_wait
                );
            },
        }).done(function (data) {
            if (data.status) {
                $("#msgSubmit").html("<div class='text-success pt-2'>" + data.message + "</div>");
                $("#fname").val('');
                $("#lname").val('');
                $("#phone").val('');
                $("#email").val('');
                $("#message").val('');
                $("#sendMessage").html(langu.send_message);
            } else {
                $("#msgSubmit").html("<div class='text-danger pt-2'>" + data.message + "</div>");
            }
        }).fail(function () {
            $("#msgSubmit").html("<div class='text-warning pt-2'>" + langu.something_went_wrong_try_again + "</div>");
            $("#sendMessage").html(langu.send_message);
        });
    } else {
        $("#msgSubmit").html("<div class='text-danger pt-2'>" + langu.please_fill_in_all_fields + "</div>");
        $("#sendMessage").html(langu.send_message);
    }
});