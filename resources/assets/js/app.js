/**
 * Wywoływana w momencie zaznaczenia jednej z odpowiedzi w quizie. Ustawia klasę na elemencie rodzica, który
 * może być potem odpowiednio ostylowany.
 */
window.selectAnswer = function (element) {
    // odznaczenie pozostałych odpowiedzi
    var questions = element.parentNode.parentNode.children;
    for (var i = 0; i < questions.length; i++) {
        questions[i].classList.remove('checked');
    }
    
    // zaznaczenie odpowiedzi
    element.parentNode.classList.add('checked');
}