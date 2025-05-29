const ctx = document.getElementById("flockChart").getContext("2d");
window.flockChart = new Chart(ctx, {
    type: "bar",
    data: {
        labels: ["0-100", "101-200", "201-500", "501+"],
        datasets: [{
            label: "Flock Count",
            data: [0, 0, 0, 0],
            backgroundColor: ["#4e79a7", "#f28e2c", "#e15759", "#76b7b2"],
            borderColor: ["#4e79a7", "#f28e2c", "#e15759", "#76b7b2"],
            borderWidth: 1
        }]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true,
                title: { display: true, text: "Number of Flocks" }
            },
            x: {
                title: { display: true, text: "Initial Bird Count" }
            }
        },
        plugins: {
            legend: { display: true }
        }
    }
});
console.log("Chart initialized:", window.flockChart);