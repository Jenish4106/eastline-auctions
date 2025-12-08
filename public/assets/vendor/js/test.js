(function () {
    const warningText = "You cannot change the tab; the admin can cancel your test.";

    let leftTab = false;
    let warningShown = false;

    function logSecurityEvent(actionType, actionDetails = null) {
        if (typeof attemptId === 'undefined' || typeof testId === 'undefined' || typeof userId === 'undefined' || typeof ipv4 === 'undefined') {
            return;
        }
        const questionNo = typeof currentQuestion !== 'undefined' ? currentQuestion + 1 : null;
        fetch(cheatingUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.csrfToken || document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                attempt_id: attemptId,
                test_id: testId,
                user_id: userId,
                question_no: questionNo,
                action_type: actionType,
                action_details: actionDetails,
                ipv4: ipv4
            })
        });
    }

    function showStrictWarning() {
        if (typeof isSubmitting !== 'undefined' && isSubmitting) return;
        if (typeof window.testSubmitted !== 'undefined' && window.testSubmitted) return;
        if (warningShown) return;
        warningShown = true;
        logSecurityEvent('tab_change', 'User left and returned to tab');
    }

    function handleVisibilityChange() {
        if (typeof isSubmitting !== 'undefined' && isSubmitting) return;
        if (typeof window.testSubmitted !== 'undefined' && window.testSubmitted) return;
        if (document.hidden) {
            leftTab = true;
            logSecurityEvent('tab_hidden', 'User switched away from tab');
        } else {
            if (leftTab) {
                showStrictWarning();
                leftTab = false;
            }
        }
    }

    function handleWindowBlur() {
        if (typeof isSubmitting !== 'undefined' && isSubmitting) return;
        if (typeof window.testSubmitted !== 'undefined' && window.testSubmitted) return;
        leftTab = true;
        logSecurityEvent('window_blur', 'Window lost focus');
    }

    function handleWindowFocus() {
        if (typeof isSubmitting !== 'undefined' && isSubmitting) return;
        if (typeof window.testSubmitted !== 'undefined' && window.testSubmitted) return;
        if (leftTab) {
            showStrictWarning();
            leftTab = false;
        }
    }

    window.addEventListener('beforeunload', function (e) {
        if (typeof isSubmitting !== 'undefined' && isSubmitting) return;
        if (typeof window.testSubmitted !== 'undefined' && window.testSubmitted) return;

        try {
            if (typeof answers !== 'undefined' && answers && Object.keys(answers).length === 0) return;
        } catch (_) { /* no-op */ }

        e.preventDefault();
        e.returnValue = '';
    });

    function preventAppTabChange() {
        document.querySelectorAll('.tab-selector').forEach(tab => {
            tab.addEventListener('click', (e) => {
                if (typeof isSubmitting !== 'undefined' && isSubmitting) return;
                if (typeof window.testSubmitted !== 'undefined' && window.testSubmitted) return;
                e.preventDefault();
                logSecurityEvent('tab_selector_click', 'Attempted to change app tab');
                showStrictWarning();
            });
        });
    }

    window.addEventListener('contextmenu', e => {
        if (typeof isSubmitting !== 'undefined' && isSubmitting) return;
        if (typeof window.testSubmitted !== 'undefined' && window.testSubmitted) return;
        e.preventDefault();
        logSecurityEvent('context_menu', 'Right-click/context menu attempt');
    });

    ['copy', 'cut', 'paste'].forEach(evt => {
        window.addEventListener(evt, e => {
            if (typeof isSubmitting !== 'undefined' && isSubmitting) return;
            if (typeof window.testSubmitted !== 'undefined' && window.testSubmitted) return;
            e.preventDefault();
            logSecurityEvent(evt, 'Attempted ' + evt);
        });
    });

    window.addEventListener('keydown', e => {
        if (typeof isSubmitting !== 'undefined' && isSubmitting) return;
        if (typeof window.testSubmitted !== 'undefined' && window.testSubmitted) return;
        if (
            e.key === 'F12' ||
            (e.ctrlKey && e.shiftKey && ['I', 'J', 'C'].includes(e.key.toUpperCase())) ||
            (e.ctrlKey && e.key.toUpperCase() === 'U')
        ) {
            e.preventDefault();
            logSecurityEvent('devtools_key', 'Attempted devtools or view source: ' + e.key);
            showStrictWarning();
        }
    });

    if (typeof document !== "undefined" && typeof window !== "undefined") {
        document.addEventListener("visibilitychange", handleVisibilityChange, false);
        window.addEventListener("blur", handleWindowBlur, false);
        window.addEventListener("focus", handleWindowFocus, false);
        preventAppTabChange();
    }

    window._TabChangeWarning = {
        show: showStrictWarning,
        disable: function () {
            document.removeEventListener("visibilitychange", handleVisibilityChange);
            window.removeEventListener("blur", handleWindowBlur);
            window.removeEventListener("focus", handleWindowFocus);
            window.removeEventListener('beforeunload', null);
            window.removeEventListener('contextmenu', null);
            ['copy', 'cut', 'paste'].forEach(evt => {
                window.removeEventListener(evt, null);
            });
            window.removeEventListener('keydown', null);
        }
    };
})();
