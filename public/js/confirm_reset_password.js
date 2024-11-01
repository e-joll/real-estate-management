// public/js/confirm_reset_password.js

document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".action-resetPassword").forEach((function (element) {
        element.addEventListener("click", (function (event) {
            event.preventDefault()
            document.querySelector("#modal-reset-password-button").addEventListener("click", (function () {
                const a = element.getAttribute("href")
                    , form = document.querySelector("#reset-password-form");
                form.setAttribute("action", a)
                form.submit()
            }))
        }))
    }))
})