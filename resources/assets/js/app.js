global.$ = require('jQuery');
$(function () {
    var clock = $('#clock-value').get(0);  
    window.clock = clock;

    // Uruchomienie zegara, jeśli istnieje
    if (window.seconds)
        window.clockTick();
});

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

/**
 * Funkcja odpowiadająca za odliczanie czasu zegara.
 */
window.clockTick = function () {
    window.seconds--;
    if (window.seconds == 0) {
        alert('Czas na rozwiązywanie testu się skończył');
        window.location.pathname = '/';
    }
    
    // Zmiana kolorów w zależności od pozostałego czasu
    if (window.seconds == 60)
        window.clock.style.color = 'red';
    else if (window.seconds == 300)
        window.clock.style.color = 'orange';

    var mins = Math.floor(window.seconds / 60);
    var secs = window.seconds % 60;
    window.clock.textContent = mins + ':' + secs.toString().padStart(2, '0');

    setTimeout(window.clockTick, 1000);
}