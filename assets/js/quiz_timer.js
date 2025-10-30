document.addEventListener('DOMContentLoaded', () => {
    const timerElement = document.getElementById('timer');
    const quizForm = document.getElementById('quiz-form');
    if (!timerElement || !quizForm) return;

    let timeRemaining = QUIZ_DATA.timeLimit * 60;
    
    const formatTime = (seconds) => {
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    };

    const updateTimerDisplay = () => {
        timerElement.textContent = formatTime(timeRemaining);
        if(timeRemaining <= 60) {
             timerElement.style.color = '#e74c3c'; // Danger color
        }
    };
    
    const countdown = setInterval(() => {
        timeRemaining--;
        updateTimerDisplay();
        
        // Save progress every 15 seconds (optional but good)
        if(timeRemaining % 15 === 0){
             // Add logic to save progress via fetch to quiz_handler.php
        }
        
        if (timeRemaining <= 0) {
            clearInterval(countdown);
            timerElement.textContent = "الوقت انتهى!";
            quizForm.submit(); // Submit the form automatically
        }
    }, 1000);
    
    // Initial display
    updateTimerDisplay();

    // Prevent user from leaving the page without confirmation
    window.addEventListener('beforeunload', (event) => {
        event.preventDefault();
        event.returnValue = 'هل أنت متأكد من مغادرة الاختبار؟ سيتم حفظ تقدمك ولكن الوقت سيستمر في العد.';
    });

});