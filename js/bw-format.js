/**
 * bw-format.js - BookWagon Global Input Formatting & Validation
 * Automatically formats inputs by class name on any page it's included in.
 *
 * Classes:
 *   .bw-phone    → Philippine phone: 0917 123 4567 (digits only, auto-spaced)
 *   .bw-postal   → PH Postal code: 4 digits only
 *   .bw-name     → Letters + spaces only, auto title-case
 *   .bw-numeric  → Numbers and decimals only (for amounts, prices)
 *   .bw-integer  → Whole numbers only (no decimals)
 */

document.addEventListener('DOMContentLoaded', function () {

    // ─────────────────────────────────────────────
    // PHONE NUMBER: 0917 123 4567
    // ─────────────────────────────────────────────
    document.querySelectorAll('.bw-phone').forEach(function (input) {
        input.setAttribute('placeholder', '0917 123 4567');
        input.setAttribute('maxlength', '13');

        input.addEventListener('keydown', function (e) {
            const allowed = [8, 9, 27, 37, 38, 39, 40, 46, 36, 35];
            if (allowed.includes(e.keyCode)) return;
            if ((e.keyCode < 48 || e.keyCode > 57) && (e.keyCode < 96 || e.keyCode > 105)) {
                e.preventDefault();
            }
        });

        input.addEventListener('input', function () {
            let digits = this.value.replace(/\D/g, '');
            if (digits.length > 11) digits = digits.slice(0, 11);

            let formatted = '';
            if (digits.length <= 4) {
                formatted = digits;
            } else if (digits.length <= 7) {
                formatted = digits.slice(0, 4) + ' ' + digits.slice(4);
            } else {
                formatted = digits.slice(0, 4) + ' ' + digits.slice(4, 7) + ' ' + digits.slice(7);
            }
            this.value = formatted;
        });

        input.addEventListener('blur', function () {
            const digits = this.value.replace(/\D/g, '');
            if (digits.length > 0 && (digits.length !== 11 || !digits.startsWith('09'))) {
                this.style.borderColor = '#ef4444';
                this.setCustomValidity('Please enter a valid Philippine mobile number starting with 09 (e.g. 0917 123 4567)');
            } else if (digits.length === 11) {
                this.style.borderColor = '#22c55e';
                this.setCustomValidity('');
            } else {
                this.style.borderColor = '';
                this.setCustomValidity('');
            }
        });

        input.addEventListener('focus', function () {
            this.style.borderColor = '';
            this.setCustomValidity('');
        });
    });


    // ─────────────────────────────────────────────
    // POSTAL CODE: 4 digits only
    // ─────────────────────────────────────────────
    document.querySelectorAll('.bw-postal').forEach(function (input) {
        input.setAttribute('placeholder', 'e.g. 8000');
        input.setAttribute('maxlength', '4');

        input.addEventListener('keydown', function (e) {
            const allowed = [8, 9, 27, 37, 38, 39, 40, 46, 36, 35];
            if (allowed.includes(e.keyCode)) return;
            if ((e.keyCode < 48 || e.keyCode > 57) && (e.keyCode < 96 || e.keyCode > 105)) {
                e.preventDefault();
            }
        });

        input.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(0, 4);
        });

        input.addEventListener('blur', function () {
            if (this.value.length > 0 && this.value.length < 4) {
                this.style.borderColor = '#ef4444';
                this.setCustomValidity('Postal code must be exactly 4 digits.');
            } else {
                this.style.borderColor = '';
                this.setCustomValidity('');
            }
        });

        input.addEventListener('focus', function () {
            this.style.borderColor = '';
            this.setCustomValidity('');
        });
    });


    // ─────────────────────────────────────────────
    // NAME FIELDS: Letters + spaces + hyphens only, auto title-case
    // ─────────────────────────────────────────────
    document.querySelectorAll('.bw-name').forEach(function (input) {
        input.addEventListener('keydown', function (e) {
            const allowed = [8, 9, 27, 32, 37, 38, 39, 40, 46, 36, 35, 189, 190]; // includes hyphen, period
            if (allowed.includes(e.keyCode)) return;
            const isLetter = (e.keyCode >= 65 && e.keyCode <= 90);
            if (!isLetter) {
                e.preventDefault();
            }
        });

        input.addEventListener('input', function () {
            let cleaned = this.value.replace(/[^a-zA-Z\s\-\.]/g, '');
            cleaned = cleaned.replace(/\b\w/g, function (char) { return char.toUpperCase(); });
            const pos = this.selectionStart;
            this.value = cleaned;
            this.setSelectionRange(pos, pos);
        });
    });


    // ─────────────────────────────────────────────
    // NUMERIC: Numbers and decimals only (for prices/amounts)
    // ─────────────────────────────────────────────
    document.querySelectorAll('.bw-numeric').forEach(function (input) {
        input.setAttribute('inputmode', 'decimal');

        input.addEventListener('keydown', function (e) {
            const allowed = [8, 9, 27, 37, 38, 39, 40, 46, 36, 35, 110, 190];
            if (allowed.includes(e.keyCode)) return;
            if ((e.keyCode < 48 || e.keyCode > 57) && (e.keyCode < 96 || e.keyCode > 105)) {
                e.preventDefault();
            }
        });

        input.addEventListener('input', function () {
            let val = this.value.replace(/[^\d.]/g, '');
            const parts = val.split('.');
            if (parts.length > 2) val = parts[0] + '.' + parts.slice(1).join('');
            if (parts[1] && parts[1].length > 2) val = parts[0] + '.' + parts[1].slice(0, 2);
            this.value = val;
        });
    });


    // ─────────────────────────────────────────────
    // INTEGER: Whole numbers only
    // ─────────────────────────────────────────────
    document.querySelectorAll('.bw-integer').forEach(function (input) {
        input.setAttribute('inputmode', 'numeric');

        input.addEventListener('keydown', function (e) {
            const allowed = [8, 9, 27, 37, 38, 39, 40, 46, 36, 35];
            if (allowed.includes(e.keyCode)) return;
            if ((e.keyCode < 48 || e.keyCode > 57) && (e.keyCode < 96 || e.keyCode > 105)) {
                e.preventDefault();
            }
        });

        input.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '');
        });
    });

});
