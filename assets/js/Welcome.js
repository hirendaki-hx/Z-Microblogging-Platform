// Step 1: Slide welcome screen
setTimeout(() => {
    document.getElementById("welcome-screen").classList.add("slide-up");

    // Step 2: Show big logo after slide completes
    setTimeout(() => {
        const logoScreen = document.getElementById("logo-screen");
        logoScreen.style.opacity = "1";
        logoScreen.style.pointerEvents = "auto";

        // Step 3: Redirect to Index.html after showing big logo
        setTimeout(() => {
            window.location.href = "Index.html";
        }, 1800);

    }, 900);

}, 2200);
