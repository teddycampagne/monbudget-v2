<style>
/* Global Styles */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    background-attachment: fixed;
    min-height: 100vh;
    display: flex;
    align-items: center;
    padding: 20px 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* Animated background particles */
body::before {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-image: 
        radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 40% 20%, rgba(255, 255, 255, 0.05) 0%, transparent 50%);
    animation: float 20s ease-in-out infinite;
    pointer-events: none;
}

@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-20px); }
}

/* Setup Container */
.setup-container {
    max-width: 900px;
    margin: 0 auto;
    animation: fadeInUp 0.6s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Setup Card */
.setup-card {
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    overflow: hidden;
    position: relative;
}

/* Setup Header */
.setup-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 40px 30px;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.setup-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    animation: pulse 3s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 0.5; }
    50% { transform: scale(1.1); opacity: 0.3; }
}

.setup-header h1 {
    font-size: 2.5rem;
    margin: 0;
    font-weight: 700;
    position: relative;
    z-index: 1;
    text-shadow: 0 2px 10px rgba(0,0,0,0.2);
}

.setup-header h1 i {
    margin-right: 15px;
    animation: bounce 2s ease-in-out infinite;
}

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.setup-header p {
    margin: 15px 0 0;
    opacity: 0.95;
    font-size: 1.1rem;
    position: relative;
    z-index: 1;
}

/* Setup Body */
.setup-body {
    padding: 50px 40px;
    min-height: 400px;
}

.setup-body h2 {
    color: #2c3e50;
    font-weight: 700;
    margin-bottom: 10px;
    font-size: 1.8rem;
}

.setup-body h3 {
    color: #34495e;
    font-weight: 600;
    margin-top: 30px;
    margin-bottom: 20px;
    font-size: 1.3rem;
}

.setup-body h5 {
    color: #5a6c7d;
    font-weight: 600;
    margin-bottom: 15px;
    font-size: 1.1rem;
}

.setup-body .lead {
    color: #6c757d;
    font-size: 1.05rem;
    line-height: 1.6;
}

/* Setup Footer */
.setup-footer {
    padding: 25px 40px;
    background: linear-gradient(to bottom, #f8f9fa 0%, #e9ecef 100%);
    border-top: 1px solid #dee2e6;
}

/* Step Indicator */
.step-indicator {
    display: flex;
    justify-content: space-between;
    margin-bottom: 40px;
    position: relative;
    padding: 0 20px;
}

.step-indicator::before {
    content: '';
    position: absolute;
    top: 22px;
    left: 10%;
    right: 10%;
    height: 3px;
    background: linear-gradient(to right, #dee2e6 0%, #dee2e6 100%);
    z-index: 0;
}

.step {
    text-align: center;
    position: relative;
    z-index: 1;
    flex: 1;
    transition: all 0.3s ease;
}

.step-circle {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: white;
    border: 3px solid #dee2e6;
    margin: 0 auto 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.1rem;
    color: #6c757d;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.step.active .step-circle {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-color: #667eea;
    color: white;
    transform: scale(1.15);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    animation: stepPulse 2s ease-in-out infinite;
}

@keyframes stepPulse {
    0%, 100% { box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4); }
    50% { box-shadow: 0 4px 25px rgba(102, 126, 234, 0.6); }
}

.step.completed .step-circle {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    border-color: #28a745;
    color: white;
    transform: scale(1.05);
}

.step.completed .step-circle i {
    font-size: 1.3rem;
}

.step-label {
    font-size: 0.9rem;
    color: #6c757d;
    font-weight: 500;
    transition: all 0.3s ease;
}

.step.active .step-label {
    color: #667eea;
    font-weight: 700;
    font-size: 0.95rem;
}

.step.completed .step-label {
    color: #28a745;
    font-weight: 600;
}

/* Requirement Items */
.requirements {
    margin-top: 20px;
}

.requirement-item {
    display: flex;
    align-items: center;
    padding: 15px 18px;
    margin-bottom: 12px;
    border-radius: 12px;
    background: #f8f9fa;
    border-left: 4px solid transparent;
    transition: all 0.3s ease;
    animation: slideIn 0.4s ease-out;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.requirement-item:hover {
    transform: translateX(5px);
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
}

.requirement-item.success {
    background: linear-gradient(to right, #d4edda 0%, #e9f7ef 100%);
    border-left-color: #28a745;
}

.requirement-item.error {
    background: linear-gradient(to right, #f8d7da 0%, #fce4e6 100%);
    border-left-color: #dc3545;
}

.requirement-icon {
    font-size: 1.8rem;
    margin-right: 18px;
    min-width: 30px;
    transition: transform 0.3s ease;
}

.requirement-item:hover .requirement-icon {
    transform: scale(1.2);
}

.requirement-icon.success {
    color: #28a745;
}

.requirement-icon.error {
    color: #dc3545;
}

.requirement-item strong {
    display: block;
    margin-bottom: 3px;
    color: #2c3e50;
}

.requirement-item .small {
    color: #6c757d;
    font-size: 0.875rem;
}

.requirement-item .flex-grow-1 {
    flex-grow: 1;
}

/* Buttons */
.btn {
    border-radius: 10px;
    padding: 12px 28px;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
    font-size: 1rem;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.btn-primary:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    background: linear-gradient(135deg, #5568d3 0%, #6a3f8f 100%);
    color: white;
}

.btn-primary:active:not(:disabled) {
    transform: translateY(0);
}

.btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-outline-primary {
    border: 2px solid #667eea;
    color: #667eea;
    background: transparent;
}

.btn-outline-primary:hover {
    background: #667eea;
    color: white;
    transform: translateY(-2px);
}

.btn-outline-secondary {
    border: 2px solid #6c757d;
    color: #6c757d;
    background: transparent;
}

.btn-outline-secondary:hover {
    background: #6c757d;
    color: white;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

/* Alerts */
.alert {
    border-radius: 12px;
    border: none;
    padding: 18px 22px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    animation: slideDown 0.4s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.alert i {
    margin-right: 10px;
    font-size: 1.2rem;
}

.alert-info {
    background: linear-gradient(to right, #d1ecf1 0%, #e7f5f8 100%);
    color: #0c5460;
    border-left: 4px solid #17a2b8;
}

.alert-success {
    background: linear-gradient(to right, #d4edda 0%, #e9f7ef 100%);
    color: #155724;
    border-left: 4px solid #28a745;
}

.alert-danger {
    background: linear-gradient(to right, #f8d7da 0%, #fce4e6 100%);
    color: #721c24;
    border-left: 4px solid #dc3545;
}

/* Form Controls */
.form-label {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 8px;
    font-size: 0.95rem;
}

.form-control, .form-select {
    border-radius: 10px;
    border: 2px solid #e9ecef;
    padding: 12px 16px;
    transition: all 0.3s ease;
    font-size: 1rem;
}

.form-control:focus, .form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
    transform: translateY(-1px);
}

.form-text {
    color: #6c757d;
    font-size: 0.875rem;
    margin-top: 5px;
}

.text-danger {
    color: #dc3545;
}

.text-muted {
    color: #6c757d;
}

.mb-0 { margin-bottom: 0; }
.mb-3 { margin-bottom: 1rem; }
.mb-4 { margin-bottom: 1.5rem; }
.mt-3 { margin-top: 1rem; }
.mt-4 { margin-top: 1.5rem; }

.d-flex { display: flex; }
.justify-content-between { justify-content: space-between; }
.align-items-center { align-items: center; }
.gap-2 { gap: 0.5rem; }

.text-center { text-align: center; }
.text-success { color: #28a745; }
.py-5 { padding-top: 3rem; padding-bottom: 3rem; }

/* Loading Spinner */
.spinner-border-sm {
    margin-right: 8px;
}

.spinner-border {
    display: inline-block;
    width: 1rem;
    height: 1rem;
    vertical-align: text-bottom;
    border: 0.25em solid currentColor;
    border-right-color: transparent;
    border-radius: 50%;
    animation: spinner-border 0.75s linear infinite;
}

@keyframes spinner-border {
    to { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 768px) {
    .setup-container {
        margin: 10px;
    }
    
    .setup-body {
        padding: 30px 20px;
    }
    
    .setup-footer {
        padding: 20px;
    }
    
    .setup-header h1 {
        font-size: 1.8rem;
    }
    
    .step-circle {
        width: 40px;
        height: 40px;
        font-size: 0.9rem;
    }
    
    .step-label {
        font-size: 0.75rem;
    }
    
    .requirement-item {
        padding: 12px 15px;
    }
    
    .btn {
        width: 100%;
        margin-bottom: 10px;
    }
}

/* Smooth scrolling */
html {
    scroll-behavior: smooth;
}

/* Selection color */
::selection {
    background: #667eea;
    color: white;
}
</style>
