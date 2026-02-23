
document.addEventListener("DOMContentLoaded", function () {
    const filter = document.getElementById("logFilter");
    if (filter) {
        filter.addEventListener("input", function () {
            const value = this.value.toLowerCase();
            document.querySelectorAll("tbody tr").forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(value) ? "" : "none";
            });
        });
    }
});
