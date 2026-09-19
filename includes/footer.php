<script>
function togglePasswordVisibility(id, icon) {
    const input = document.getElementById(id);
    if (!input) return;
    input.type = input.type === 'password' ? 'text' : 'password';
    if (icon) icon.classList.toggle('fa-eye-slash');
}

function updatePasswordStrengthUI(inputId, barId) {
    const input = document.getElementById(inputId);
    const bar = document.getElementById(barId);
    if (!input || !bar) return;
    const update = () => {
        const length = input.value.length;
        bar.style.width = Math.min(length * 10, 100) + '%';
    };
    input.addEventListener('input', update);
    update();
}

function showToast(message) {
    window.alert(message);
}
</script>
</body>
</html>
