window.doTogglePassword = function (el) {
    var wrap = el.closest(".controls");
    var input = wrap && wrap.querySelector('input[name="Password"]');
    if (!input) return;
    var show = input.type === "password";
    input.type = show ? "text" : "password";
    el.textContent = show ? "��" : "👁";
    el.setAttribute("aria-pressed", show ? "true" : "false");
};

document.addEventListener("click", function (e) {
    var el = e.target.closest(".snappy-eye-toggle");
    if (!el) return;
    e.preventDefault();
    window.doTogglePassword(el);
});
