import { initCursor } from './behaviors/cursor';
import { initFlashMessages } from './behaviors/flash';
import { initPasswordToggles } from './behaviors/password-toggle';
import { initSubmitLocks } from './behaviors/submit-lock';

document.addEventListener('DOMContentLoaded', () => {
    initSubmitLocks();
    initPasswordToggles();
    initFlashMessages();
    initCursor();
});
