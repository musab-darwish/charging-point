import { initConfirmDialogs } from './behaviors/confirm';
import { initCursor } from './behaviors/cursor';
import { initDayFilter } from './behaviors/day-filter';
import { initDayNav } from './behaviors/day-nav';
import { initFlashMessages } from './behaviors/flash';
import { initFreezer } from './behaviors/freezer';
import { initLiveSearch } from './behaviors/live-search';
import { initPasswordToggles } from './behaviors/password-toggle';
import { initPaymentDialog } from './behaviors/payment-dialog';
import { initSubmitLocks } from './behaviors/submit-lock';
import { initWorkEntry } from './behaviors/work-entry';

document.addEventListener('DOMContentLoaded', () => {
    initConfirmDialogs();
    initSubmitLocks();
    initPasswordToggles();
    initFlashMessages();
    initLiveSearch();
    initWorkEntry();
    initPaymentDialog();
    initDayNav();
    initDayFilter();
    initFreezer();
    initCursor();
});
