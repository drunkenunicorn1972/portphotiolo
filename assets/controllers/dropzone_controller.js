import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'preview', 'dropzone'];

    connect() {
        console.log('Dropzone controller connected!'); // Debug line
        this.setupDropzone();
    }

    setupDropzone() {
        const dropzone = this.dropzoneTarget;
        const input = this.inputTarget;

        // Prevent default drag behaviors
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, this.preventDefaults.bind(this), false);
            document.body.addEventListener(eventName, this.preventDefaults.bind(this), false);
        });

        // Highlight drop zone when item is dragged over it
        ['dragenter', 'dragover'].forEach(eventName => {
            dropzone.addEventListener(eventName, () => this.highlight(dropzone), false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, () => this.unhighlight(dropzone), false);
        });

        // Handle dropped files
        dropzone.addEventListener('drop', this.handleDrop.bind(this), false);

        // Handle file input change (when clicking to select files)
        input.addEventListener('change', this.handleFiles.bind(this), false);
    }

    preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    highlight(element) {
        element.classList.add('dragover');
    }

    unhighlight(element) {
        element.classList.remove('dragover');
    }

    handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;

        // Assign files to the hidden file input
        this.inputTarget.files = files;
        this.handleFiles();
    }

    handleFiles() {
        const files = [...this.inputTarget.files];
        this.updatePreview(files);
    }

    updatePreview(files) {
        this.previewTarget.innerHTML = '';

        if (files.length === 0) {
            this.previewTarget.innerHTML = '<p class="text-muted">No files selected</p>';
            return;
        }

        const fileList = document.createElement('div');
        fileList.className = 'file-list';

        files.forEach(file => {
            const fileItem = document.createElement('div');
            fileItem.className = 'file-item d-flex align-items-center mb-2';

            if (file.type.startsWith('image/')) {
                const img = document.createElement('img');
                img.file = file;
                img.className = 'file-thumbnail me-2';
                img.style.width = '50px';
                img.style.height = '50px';
                img.style.objectFit = 'cover';
                img.style.borderRadius = '4px';

                const reader = new FileReader();
                reader.onload = (e) => {
                    img.src = e.target.result;
                };
                reader.readAsDataURL(file);

                fileItem.appendChild(img);
            }

            const fileName = document.createElement('span');
            fileName.textContent = file.name;
            fileName.className = 'file-name';
            fileItem.appendChild(fileName);

            const fileSize = document.createElement('span');
            fileSize.textContent = ` (${this.formatFileSize(file.size)})`;
            fileSize.className = 'file-size text-muted ms-2';
            fileItem.appendChild(fileSize);

            fileList.appendChild(fileItem);
        });

        this.previewTarget.appendChild(fileList);
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }

    triggerFileInput() {
        this.inputTarget.click();
    }
}
