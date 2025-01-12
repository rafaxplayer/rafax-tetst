(function ($) {
    const config = RafxTestConfig;
    const { uniqueId, itemsPerPage, testData, showResults } = config;

    let currentPage = 0;
    const results = Array(testData.length).fill(null);

    function showPage(page) {
        $(`#${uniqueId} .question-page`).hide();
        $(`#${uniqueId} .question-page[data-page="${page}"]`).show();

        $(`#${uniqueId}-prev-button`).toggle(page > 0);
        $(`#${uniqueId}-next-button`).toggle(page < Math.ceil(testData.length / itemsPerPage) - 1);
        $(`#${uniqueId}-result-button`).toggle(page === Math.ceil(testData.length / itemsPerPage) - 1);
    }

    function updateResults() {
        let correct = 0, incorrect = 0;

        testData.forEach((question, index) => {
            if (results[index] === question.correct) {
                correct++;
            } else if (results[index] !== null) {
                incorrect++;
            }
        });

        $(`#${uniqueId}-correct`).text(correct);
        $(`#${uniqueId}-incorrect`).text(incorrect);
    }

    $(document).ready(function () {
        showPage(currentPage);

        $(`#${uniqueId}-next-button`).on('click', function () {
            if (currentPage < Math.ceil(testData.length / itemsPerPage) - 1) {
                currentPage++;
                showPage(currentPage);
            }
        });

        $(`#${uniqueId}-prev-button`).on('click', function () {
            if (currentPage > 0) {
                currentPage--;
                showPage(currentPage);
            }
        });

        $(`#${uniqueId}-form input[type="radio"]`).on('change', function () {
            const questionIndex = $(this).attr('name').split('-')[1];
            const selectedValue = $(this).val();
            results[questionIndex] = parseInt(selectedValue, 10);

            if (showResults === 'yes') {
                updateResults();
            }
        });

        $(`#${uniqueId}-result-button`).on('click', function () {
            updateResults();
            $(`#${uniqueId}-result`).show();
        });
    });
})(jQuery);
