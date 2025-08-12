/**
 * Universal Loading States for Action Buttons
 * 
 * This utility provides consistent loading states across all action buttons
 * in the Finance Transaction System.
 */

class LoadingStateManager {
    constructor() {
        this.loadingButtons = new Map();
        this.defaultSpinner = this.createSpinner();
    }

    /**
     * Create a loading spinner element
     */
    createSpinner(size = 'sm') {
        const spinner = document.createElement('span');
        spinner.className = `spinner-border spinner-border-${size} loading-spinner`;
        spinner.setAttribute('role', 'status');
        spinner.setAttribute('aria-hidden', 'true');
        spinner.style.marginRight = '8px';
        return spinner;
    }

    /**
     * Set button to loading state
     */
    setLoading(buttonElement, loadingText = 'Processing...') {
        if (!buttonElement) return;

        // Store original state
        const originalData = {
            text: buttonElement.innerHTML,
            disabled: buttonElement.disabled,
            className: buttonElement.className
        };
        
        this.loadingButtons.set(buttonElement, originalData);

        // Set loading state
        buttonElement.disabled = true;
        buttonElement.innerHTML = '';
        
        // Add spinner
        const spinner = this.createSpinner();
        buttonElement.appendChild(spinner);
        
        // Add loading text
        const textNode = document.createTextNode(loadingText);
        buttonElement.appendChild(textNode);
        
        // Add loading class
        buttonElement.classList.add('btn-loading');
    }

    /**
     * Remove loading state and restore original button
     */
    removeLoading(buttonElement) {
        if (!buttonElement || !this.loadingButtons.has(buttonElement)) return;

        const originalData = this.loadingButtons.get(buttonElement);
        
        // Restore original state
        buttonElement.innerHTML = originalData.text;
        buttonElement.disabled = originalData.disabled;
        buttonElement.className = originalData.className;
        
        // Remove from tracking
        this.loadingButtons.delete(buttonElement);
    }

    /**
     * Set loading state for form submission
     */
    setFormLoading(formElement, submitButtonSelector = 'button[type="submit"], input[type="submit"]') {
        if (!formElement) return;

        const submitButtons = formElement.querySelectorAll(submitButtonSelector);
        submitButtons.forEach(button => {
            this.setLoading(button, 'Submitting...');
        });

        // Disable all form inputs
        const inputs = formElement.querySelectorAll('input, select, textarea, button');
        inputs.forEach(input => {
            if (!input.hasAttribute('data-keep-enabled')) {
                input.disabled = true;
                input.classList.add('form-loading');
            }
        });
    }

    /**
     * Remove loading state from form
     */
    removeFormLoading(formElement) {
        if (!formElement) return;

        // Re-enable form inputs
        const inputs = formElement.querySelectorAll('input, select, textarea, button');
        inputs.forEach(input => {
            if (input.classList.contains('form-loading')) {
                input.disabled = false;
                input.classList.remove('form-loading');
            }
        });

        // Remove loading from submit buttons
        const submitButtons = formElement.querySelectorAll('button[type="submit"], input[type="submit"]');
        submitButtons.forEach(button => {
            this.removeLoading(button);
        });
    }

    /**
     * Handle AJAX form submission with loading states
     */
    handleAjaxForm(formElement, options = {}) {
        if (!formElement) return;

        const {
            url = formElement.action,
            method = formElement.method || 'POST',
            onSuccess = () => {},
            onError = () => {},
            onComplete = () => {},
            loadingText = 'Processing...'
        } = options;

        formElement.addEventListener('submit', (e) => {
            e.preventDefault();
            
            // Set loading state
            this.setFormLoading(formElement);
            
            // Get form data
            const formData = new FormData(formElement);
            
            // Make AJAX request
            fetch(url, {
                method: method,
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                onSuccess(data);
            })
            .catch(error => {
                console.error('Form submission error:', error);
                onError(error);
            })
            .finally(() => {
                // Remove loading state
                this.removeFormLoading(formElement);
                onComplete();
            });
        });
    }

    /**
     * Handle button click with loading state
     */
    handleButtonClick(buttonElement, asyncFunction, loadingText = 'Processing...') {
        if (!buttonElement) return;

        buttonElement.addEventListener('click', async (e) => {
            e.preventDefault();
            
            // Set loading state
            this.setLoading(buttonElement, loadingText);
            
            try {
                await asyncFunction(e);
            } catch (error) {
                console.error('Button action error:', error);
            } finally {
                // Remove loading state
                this.removeLoading(buttonElement);
            }
        });
    }

    /**
     * Auto-initialize loading states for common elements
     */
    autoInitialize() {
        // Initialize forms with data-ajax attribute
        document.querySelectorAll('form[data-ajax]').forEach(form => {
            this.handleAjaxForm(form, {
                onSuccess: (data) => {
                    if (data.success) {
                        this.showSuccessMessage(data.message || 'Operation completed successfully');
                        if (data.redirect) {
                            setTimeout(() => window.location.href = data.redirect, 1500);
                        }
                    } else {
                        this.showErrorMessage(data.message || 'Operation failed');
                    }
                },
                onError: (error) => {
                    this.showErrorMessage('An error occurred. Please try again.');
                }
            });
        });

        // Initialize buttons with data-loading attribute
        document.querySelectorAll('button[data-loading], input[type="submit"][data-loading]').forEach(button => {
            const loadingText = button.getAttribute('data-loading') || 'Processing...';
            
            button.addEventListener('click', (e) => {
                // Only set loading if not in a form (forms handle their own loading)
                if (!button.closest('form')) {
                    this.setLoading(button, loadingText);
                    
                    // Auto-remove loading after 5 seconds as fallback
                    setTimeout(() => {
                        this.removeLoading(button);
                    }, 5000);
                }
            });
        });
    }

    /**
     * Show success message
     */
    showSuccessMessage(message) {
        this.showMessage(message, 'success');
    }

    /**
     * Show error message
     */
    showErrorMessage(message) {
        this.showMessage(message, 'error');
    }

    /**
     * Show message with type
     */
    showMessage(message, type = 'info') {
        // Try to use existing alert system or create simple alert
        if (typeof alert === 'function') {
            alert(message);
        } else {
            console.log(`${type.toUpperCase()}: ${message}`);
        }
    }
}

// Create global instance
window.LoadingStateManager = new LoadingStateManager();

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.LoadingStateManager.autoInitialize();
    });
} else {
    window.LoadingStateManager.autoInitialize();
}

// Utility functions for easy access
window.setButtonLoading = (button, text) => window.LoadingStateManager.setLoading(button, text);
window.removeButtonLoading = (button) => window.LoadingStateManager.removeLoading(button);
window.setFormLoading = (form) => window.LoadingStateManager.setFormLoading(form);
window.removeFormLoading = (form) => window.LoadingStateManager.removeFormLoading(form);
