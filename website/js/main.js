const menuButton=document.querySelector('.menu-btn');
const navLinks=document.querySelector('.nav-links');
if(menuButton&&navLinks){menuButton.addEventListener('click',()=>{const open=navLinks.classList.toggle('open');menuButton.setAttribute('aria-expanded',open?'true':'false');});}
