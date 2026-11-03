import { initConfirmDialogs } from './behaviors/confirm';
import { initCursor } from './behaviors/cursor';
import { initFlashMessages } from './behaviors/flash';
import { initLiveSearch } from './behaviors/live-search';
import { initPasswordToggles } from './behaviors/password-toggle';
import { initSubmitLocks } from './behaviors/submit-lock';

document.addEventListener('DOMContentLoaded', () => {
    initConfirmDialogs();
    initSubmitLocks();
    initPasswordToggles();
    initFlashMessages();
    initLiveSearch();
    initCursor();
});
