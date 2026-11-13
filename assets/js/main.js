/**
 * CourtIQ: A Web-Based Hostel Management System
 * Main Frontend Interactions & Navigation
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Mobile Menu Navigation Toggle
    const mobileToggle = document.getElementById('mobileToggle');
    const mainNav = document.getElementById('mainNav');

    if (mobileToggle && mainNav) {
        mobileToggle.addEventListener('click', () => {
            const isExpanded = mobileToggle.getAttribute('aria-expanded') === 'true';
            mobileToggle.setAttribute('aria-expanded', String(!isExpanded));
            mainNav.classList.toggle('active');
        });

        // Close menu on ESC key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && mainNav.classList.contains('active')) {
                mainNav.classList.remove('active');
                mobileToggle.setAttribute('aria-expanded', 'false');
                mobileToggle.focus();
            }
        });

        // Close menu when clicking outside
        document.addEventListener('click', (e) => {
            if (
                mainNav.classList.contains('active') &&
                !mainNav.contains(e.target) &&
                !mobileToggle.contains(e.target)
            ) {
                mainNav.classList.remove('active');
                mobileToggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // 2. Smooth Scrolling with Sticky Header Offset
    const internalLinks = document.querySelectorAll('a[href*="#"]');
    const header = document.getElementById('siteHeader');
    const headerOffset = header ? header.offsetHeight + 16 : 80;

    internalLinks.forEach((link) => {
        link.addEventListener('click', (event) => {
            const href = link.getAttribute('href');
            if (!href || href === '#' || href === '#!') return;

            // Extract the hash
            const hashIndex = href.indexOf('#');
            if (hashIndex === -1) return;

            const targetId = href.substring(hashIndex);
            const targetElement = document.querySelector(targetId);

            if (targetElement) {
                event.preventDefault();

                const contentMenu = targetElement.closest('.landing-content-menu');
                if (contentMenu) {
                    contentMenu.open = true;
                    contentMenu.querySelectorAll('.landing-content > section').forEach((section) => {
                        section.classList.toggle('is-selected', section === targetElement);
                    });
                }

                // If it's a cross-page link and we're not on the right page, allow default
                const currentPath = window.location.pathname;
                const linkPath = href.substring(0, hashIndex);

                if (linkPath && !currentPath.endsWith(linkPath) && !linkPath.endsWith('index.php')) {
                    return;
                }

                if (targetId === '#home') {
                    document.body.classList.remove('section-view');
                    const landingMenu = document.querySelector('.landing-content-menu');
                    if (landingMenu) {
                        landingMenu.open = false;
                        landingMenu.querySelectorAll('.landing-content > section').forEach((section) => {
                            section.classList.remove('is-selected');
                        });
                    }
                    window.history.replaceState(null, '', window.location.pathname);
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    return;
                }

                document.body.classList.add('section-view');
                window.history.pushState(null, '', targetId);

                // Close mobile menu if open
                if (mainNav && mainNav.classList.contains('active')) {
                    mainNav.classList.remove('active');
                    if (mobileToggle) mobileToggle.setAttribute('aria-expanded', 'false');
                }

                const elementPosition = targetElement.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });

                // Set focus for accessibility
                targetElement.setAttribute('tabindex', '-1');
                targetElement.focus({ preventScroll: true });
            }
        });
    });

    const landingMenu = document.querySelector('.landing-content-menu');
    const landingHash = window.location.hash;
    if (landingMenu && landingHash) {
        const selectedSection = landingMenu.querySelector(landingHash);
        if (selectedSection) {
            document.body.classList.add('section-view');
            landingMenu.open = true;
            landingMenu.querySelectorAll('.landing-content > section').forEach((section) => {
                section.classList.toggle('is-selected', section === selectedSection);
            });
            window.scrollTo({ top: 0, behavior: 'instant' });
        }
    }

    // 3. Scrollspy Navigation Indicator
    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('.main-nav .nav-link');

    if (sections.length > 0 && navLinks.length > 0 && 'IntersectionObserver' in window) {
        const observerOptions = {
            root: null,
            rootMargin: '-20% 0px -70% 0px',
            threshold: 0
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    const id = entry.target.getAttribute('id');
                    navLinks.forEach((navLink) => {
                        const href = navLink.getAttribute('href');
                        if (href && href.includes('#' + id)) {
                            navLink.classList.add('active');
                        } else {
                            navLink.classList.remove('active');
                        }
                    });
                }
            });
        }, observerOptions);

        sections.forEach((sec) => observer.observe(sec));
    }
});
