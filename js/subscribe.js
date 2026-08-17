//
// نموذج الاشتراك بالنشرة البريدية (id="newsletterForm") — يرسل الطلب الفعلي
// إلى api/subscribe (نظام المراسلة الحقيقي بلوحة التحكم)، بنفس أسلوب
// js/contact.js تماماً (honeypot + وقت التحميل لرصد البوتات).
//
var newsletterFormLoadedAt = Math.floor(Date.now() / 1000);

$(document).on("submit", "#newsletterForm", function (e) {
    e.preventDefault();

    var $form = $(this);
    var $email = $form.find("#newsletterEmail");
    var $note = $form.siblings("#newsletterNote").length ? $form.siblings("#newsletterNote") : $form.parent().find("#newsletterNote");
    var $btn = $form.find("button[type=submit]");
    var btnOriginalHtml = $btn.html();
    var email = $email.val();
    var website = $form.find("#newsletterWebsite").val() || ""; // حقل honeypot، يجب أن يبقى فارغاً

    if (!email) return;

    $.ajax({
        type: "POST",
        url: "api/subscribe",
        data: JSON.stringify({
            action: "subscribe",
            email: email,
            website: website,
            loaded_at: newsletterFormLoadedAt,
        }),
        dataType: "json",
        encode: true,
        beforeSend: function () {
            $btn.prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i>');
        },
    })
        .done(function (data) {
            if ($note.length) {
                $note.text(data.message || "");
            }
            if (data.status) {
                $form.trigger("reset");
            }
        })
        .fail(function () {
            if ($note.length && typeof langu !== "undefined") {
                $note.text(langu.something_went_wrong_try_again || "");
            }
        })
        .always(function () {
            $btn.prop("disabled", false).html(btnOriginalHtml);
        });
});
