// Excel Import JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Get the Excel upload form
    const excelUploadForm = document.getElementById('excel-upload-form');
    
    if (excelUploadForm) {
        excelUploadForm.addEventListener('submit', handleExcelUpload);
    }
});

// Handle Excel Upload
function handleExcelUpload(event) {
    event.preventDefault();
    
    // Get form elements
    const form = event.target;
    const fileInput = form.querySelector('#excel-file');
    const submitButton = form.querySelector('button[type="submit"]');
    const progressContainer = form.querySelector('.progress-container');
    const progressBar = progressContainer.querySelector('.progress-bar');
    const importResults = document.getElementById('import-results');
    const recordsImported = document.getElementById('records-imported');
    const duplicatesFound = document.getElementById('duplicates-found');
    
    // Check if a file was selected
    if (!fileInput.files.length) {
        window.appUtils.showNotification('Please select an Excel file to import.', 'error');
        return;
    }
    
    // Create FormData object
    const formData = new FormData();
    formData.append('excel-file', fileInput.files[0]);
    
    // Show progress container and disable submit button
    progressContainer.classList.remove('hidden');
    submitButton.disabled = true;
    importResults.classList.add('hidden');
    
    // Animate progress bar for visual feedback
    gsap.to(progressBar, {
        width: '90%',
        duration: 2,
        ease: 'power1.out'
    });
    
    // Send the file to the server
    fetch('php/import-handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        // Complete the progress bar animation
        gsap.to(progressBar, {
            width: '100%',
            duration: 0.5,
            ease: 'power1.out',
            onComplete: () => {
                // After a short delay, hide the progress bar
                setTimeout(() => {
                    progressContainer.classList.add('hidden');
                    
                    // Reset the progress bar for next time
                    gsap.set(progressBar, { width: '0%' });
                    
                    // Enable submit button
                    submitButton.disabled = false;
                }, 500);
            }
        });
        
        // Process the response
        if (data.success) {
            // Show success notification
            window.appUtils.showNotification('Guest list imported successfully!', 'success');
            
            // Update import results
            if (recordsImported) recordsImported.textContent = data.data.imported;
            if (duplicatesFound) duplicatesFound.textContent = data.data.duplicates;
            
            // Show import results section
            importResults.classList.remove('hidden');
            
            // Animate results with GSAP
            gsap.from(importResults, {
                y: 20,
                opacity: 0,
                duration: 0.5,
                ease: 'power2.out'
            });
            
            // Clear the file input
            fileInput.value = '';
            document.getElementById('file-name').textContent = 'No file chosen';
            
            // Refresh dashboard stats
            if (typeof loadDashboardStats === 'function') {
                loadDashboardStats();
            }
            
            // Refresh guest list
            if (typeof loadGuestList === 'function') {
                loadGuestList(1);
            }
            
            // Log any errors from the import
            if (data.data.errors && data.data.errors.length > 0) {
                console.warn('Import completed with some errors:', data.data.errors);
            }
        } else {
            // Show error notification
            window.appUtils.showNotification('Error importing guests: ' + data.message, 'error');
        }
    })
    .catch(error => {
        // Handle errors
        console.error('Error during file upload:', error);
        
        // Hide progress container and enable submit button
        progressContainer.classList.add('hidden');
        submitButton.disabled = false;
        
        // Show error notification
        window.appUtils.showNotification('Error uploading file. Please try again.', 'error');
    });
}

// Preview Excel Data (for future enhancement)
function previewExcelData(file) {
    // This is a placeholder for a feature that could be added
    // It would use FileReader API to read the Excel file client-side
    // and show a preview of the data before importing
    
    // Example implementation would use a library like SheetJS
    /*
    const reader = new FileReader();
    
    reader.onload = function(e) {
        const data = new Uint8Array(e.target.result);
        const workbook = XLSX.read(data, { type: 'array' });
        
        // Get the first worksheet
        const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
        
        // Convert to JSON
        const jsonData = XLSX.utils.sheet_to_json(firstSheet, { header: 1 });
        
        // Display preview (first 5 rows)
        displayPreview(jsonData.slice(0, 5));
    };
    
    reader.readAsArrayBuffer(file);
    */
}

// Validate Excel Data
function validateExcelData(data) {
    // This is a placeholder for a feature that could be added
    // It would validate the Excel data structure before importing
    
    const errors = [];
    
    // Check if data has the required columns
    const requiredColumns = ['Nom', 'Postnom', 'Email', 'Téléphone'];
    
    // Check header row
    if (!data[0] || data[0].length < requiredColumns.length) {
        errors.push('The Excel file is missing required columns. Please ensure it has: ' + requiredColumns.join(', '));
    } else {
        // Check each required column exists
        requiredColumns.forEach((column, index) => {
            if (!data[0][index] || data[0][index].trim() !== column) {
                errors.push(`Column ${index + 1} should be "${column}" but found "${data[0][index] || 'empty'}" instead.`);
            }
        });
    }
    
    // Check data rows
    for (let i = 1; i < data.length; i++) {
        const row = data[i];
        
        // Skip empty rows
        if (!row || row.length === 0 || (row.length === 1 && !row[0])) {
            continue;
        }
        
        // Check each cell in the row
        if (row.length < requiredColumns.length) {
            errors.push(`Row ${i + 1} is missing data. Expected ${requiredColumns.length} columns but found ${row.length}.`);
        } else {
            // Check email format
            const email = row[2];
            if (email && !isValidEmail(email)) {
                errors.push(`Row ${i + 1} has an invalid email format: "${email}".`);
            }
            
            // Check for empty cells
            for (let j = 0; j < requiredColumns.length; j++) {
                if (!row[j]) {
                    errors.push(`Row ${i + 1}, Column "${requiredColumns[j]}" is empty.`);
                }
            }
        }
    }
    
    return {
        isValid: errors.length === 0,
        errors: errors
    };
}

// Validate Email Format
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}