document.addEventListener('DOMContentLoaded', function() {
    // ハンバーガーメニュー
    const mobileMenu = document.getElementById('mobile-menu');
    const mainNav = document.getElementById('main-nav');
    
    if (mobileMenu && mainNav) {
        mobileMenu.addEventListener('click', function() {
            mobileMenu.classList.toggle('active');
            mainNav.classList.toggle('active');
        });

        // リンククリック時に閉じる
        const navLinks = mainNav.querySelectorAll('a');
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                mobileMenu.classList.remove('active');
                mainNav.classList.remove('active');
            });
        });
    }

    // スクロールアニメーション (Intersection Observer)
    const observerOptions = {
        threshold: 0.1
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, observerOptions);

    document.querySelectorAll('.fade-up').forEach(el => {
        observer.observe(el);
    });
    
    // reCAPTCHA (Contact Form)
    const submitButton = document.getElementById('submitFormButton');
    const recaptchaTokenField = document.getElementById('recaptcha_token');
    const contactForm = document.querySelector('form');

    if (contactForm && submitButton && recaptchaTokenField) {
        submitButton.addEventListener('click', function(event) {
            event.preventDefault();
            // Google reCAPTCHA key (your site key)
            grecaptcha.ready(function() {
                grecaptcha.execute('6LdkloErAAAAAHW7rMxD5FePWX5q5W7EeSjTbTvO', { action: 'submit_contact' }).then(function(token) {
                    recaptchaTokenField.value = token;
                    contactForm.submit();
                });
            });
        });
    }
});