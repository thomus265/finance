/**
 * Chart.js Debug Utility
 * 
 * Provides debugging tools and fallbacks for Chart.js issues
 */

class ChartDebugger {
    constructor() {
        this.debugMode = localStorage.getItem('chart-debug') === 'true';
        this.init();
    }

    init() {
        if (this.debugMode) {
            console.log('🔍 Chart Debug Mode Enabled');
        }
        
        // Check Chart.js availability
        this.checkChartJs();
        
        // Add debug controls if in debug mode
        if (this.debugMode) {
            this.addDebugControls();
        }
    }

    checkChartJs() {
        const checkInterval = setInterval(() => {
            if (typeof Chart !== 'undefined') {
                clearInterval(checkInterval);
                this.onChartJsLoaded();
            }
        }, 100);

        // Timeout after 10 seconds
        setTimeout(() => {
            clearInterval(checkInterval);
            if (typeof Chart === 'undefined') {
                this.onChartJsLoadFailed();
            }
        }, 10000);
    }

    onChartJsLoaded() {
        console.log('✅ Chart.js loaded successfully');
        console.log('📊 Chart.js version:', Chart.version);
        
        if (this.debugMode) {
            console.log('📋 Available Chart types:', Object.keys(Chart.registry.controllers.items));
        }
        
        // Trigger custom event
        document.dispatchEvent(new CustomEvent('chartjs:loaded', {
            detail: { version: Chart.version }
        }));
    }

    onChartJsLoadFailed() {
        console.error('❌ Chart.js failed to load');
        
        // Show user-friendly error message
        this.showChartError('Chart library failed to load. Please refresh the page or check your internet connection.');
        
        // Try to load fallback
        this.loadFallbackChart();
    }

    showChartError(message) {
        const chartContainers = document.querySelectorAll('[id*="Chart"], [id*="chart"]');
        
        chartContainers.forEach(container => {
            if (container.tagName.toLowerCase() === 'canvas' || container.tagName.toLowerCase() === 'div') {
                container.innerHTML = `
                    <div class="chart-error text-center p-4">
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Chart Unavailable</strong><br>
                            <small>${message}</small>
                        </div>
                        <button class="btn btn-sm btn-outline-primary" onclick="location.reload()">
                            <i class="bi bi-arrow-clockwise"></i> Refresh Page
                        </button>
                    </div>
                `;
            }
        });
    }

    loadFallbackChart() {
        // Try to load Chart.js from alternative CDN (UMD build)
        const fallbackScript = document.createElement('script');
        fallbackScript.src = 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.js';
        fallbackScript.onload = () => {
            console.log('✅ Fallback Chart.js loaded');
            this.onChartJsLoaded();
        };
        fallbackScript.onerror = () => {
            console.error('❌ Fallback Chart.js also failed to load');
            // Try one more fallback with older version
            this.loadLegacyFallback();
        };

        document.head.appendChild(fallbackScript);
    }

    loadLegacyFallback() {
        // Final fallback to Chart.js v3 which has better compatibility
        const legacyScript = document.createElement('script');
        legacyScript.src = 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js';
        legacyScript.onload = () => {
            console.log('✅ Legacy Chart.js v3 loaded as fallback');
            this.onChartJsLoaded();
        };
        legacyScript.onerror = () => {
            console.error('❌ All Chart.js fallbacks failed to load');
            this.showChartError('Unable to load chart library from multiple sources. Please check your internet connection.');
        };

        document.head.appendChild(legacyScript);
    }

    addDebugControls() {
        // Add debug panel to page
        const debugPanel = document.createElement('div');
        debugPanel.id = 'chart-debug-panel';
        debugPanel.style.cssText = `
            position: fixed;
            top: 10px;
            right: 10px;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 10px;
            border-radius: 5px;
            font-size: 12px;
            z-index: 9999;
            max-width: 300px;
        `;
        
        debugPanel.innerHTML = `
            <div><strong>📊 Chart Debug Panel</strong></div>
            <div>Chart.js: <span id="chartjs-status">Loading...</span></div>
            <div>Charts: <span id="chart-count">0</span></div>
            <button onclick="chartDebugger.refreshCharts()" style="margin-top: 5px; padding: 2px 5px;">Refresh Charts</button>
            <button onclick="chartDebugger.toggleDebug()" style="margin-top: 5px; padding: 2px 5px;">Disable Debug</button>
        `;
        
        document.body.appendChild(debugPanel);
        
        // Update status
        this.updateDebugPanel();
    }

    updateDebugPanel() {
        const statusElement = document.getElementById('chartjs-status');
        const countElement = document.getElementById('chart-count');
        
        if (statusElement) {
            statusElement.textContent = typeof Chart !== 'undefined' ? 
                `✅ v${Chart.version}` : '❌ Not loaded';
        }
        
        if (countElement) {
            const chartElements = document.querySelectorAll('canvas[id*="Chart"], canvas[id*="chart"]');
            countElement.textContent = chartElements.length;
        }
    }

    refreshCharts() {
        console.log('🔄 Refreshing charts...');
        
        // Trigger chart refresh event
        document.dispatchEvent(new CustomEvent('charts:refresh'));
        
        // Update debug panel
        setTimeout(() => this.updateDebugPanel(), 1000);
    }

    toggleDebug() {
        this.debugMode = !this.debugMode;
        localStorage.setItem('chart-debug', this.debugMode.toString());
        
        if (this.debugMode) {
            location.reload();
        } else {
            const debugPanel = document.getElementById('chart-debug-panel');
            if (debugPanel) {
                debugPanel.remove();
            }
        }
    }

    // Static method to enable debug mode
    static enableDebug() {
        localStorage.setItem('chart-debug', 'true');
        console.log('🔍 Chart debug mode enabled. Refresh the page to see debug panel.');
    }

    static disableDebug() {
        localStorage.setItem('chart-debug', 'false');
        console.log('🔍 Chart debug mode disabled.');
    }
}

// Initialize chart debugger
const chartDebugger = new ChartDebugger();

// Global functions for console access
window.enableChartDebug = ChartDebugger.enableDebug;
window.disableChartDebug = ChartDebugger.disableDebug;
window.chartDebugger = chartDebugger;

// Enhanced chart initialization helper
window.initChartSafely = function(chartId, chartConfig, retries = 3) {
    const canvas = document.getElementById(chartId);
    if (!canvas) {
        console.error(`Chart canvas '${chartId}' not found`);
        return null;
    }

    if (typeof Chart === 'undefined') {
        console.error('Chart.js not loaded, cannot initialize chart');
        chartDebugger.showChartError('Chart library not available');
        return null;
    }

    try {
        const chart = new Chart(canvas, chartConfig);
        console.log(`✅ Chart '${chartId}' initialized successfully`);
        return chart;
    } catch (error) {
        console.error(`❌ Failed to initialize chart '${chartId}':`, error);
        
        if (retries > 0) {
            console.log(`🔄 Retrying chart '${chartId}' initialization... (${retries} attempts left)`);
            setTimeout(() => {
                window.initChartSafely(chartId, chartConfig, retries - 1);
            }, 1000);
        } else {
            chartDebugger.showChartError(`Failed to initialize chart after multiple attempts: ${error.message}`);
        }
        
        return null;
    }
};

// Console helper messages
console.log('📊 Chart Debug Utility loaded');
console.log('💡 Use enableChartDebug() to enable debug mode');
console.log('💡 Use disableChartDebug() to disable debug mode');
console.log('💡 Use initChartSafely(id, config) for safer chart initialization');
