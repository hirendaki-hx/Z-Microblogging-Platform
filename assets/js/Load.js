//window.addEventListener("load", function () {
  //  document.getElementById("loading-screen").style.display = "none";
    //document.querySelector(".container").style.display = "block";
//});
window.addEventListener("load", function () {
    setTimeout(() => {
        document.getElementById("loading-screen").style.display = "none";
        document.querySelector(".container").style.display = "block";
    }, 2000); // 2 seconds delay for testing
});