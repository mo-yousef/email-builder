document.addEventListener('alpine:init', () => {
    Alpine.data('emailTemplateBuilder', (initialTemplateData) => ({
        initialDataSnapshot: null, // To store the initial state for reset
        template: null, // Will be initialized in init
        currentLang: 'en', // Default language
        activeTextarea: null, // To keep track of the focused textarea for insertions
        isLoading: false, // To provide UI feedback during save

        init() {
            this.template = JSON.parse(JSON.stringify(initialTemplateData)); // Deep copy
            this.initialDataSnapshot = JSON.parse(JSON.stringify(initialTemplateData)); // Store snapshot for reset

            const sectionsList = document.getElementById('etb-sections-list');
            if (sectionsList) {
                $(sectionsList).sortable({
                    handle: '.etb-section-header',
                    update: (event, ui) => {
                        const sectionId = ui.item.data('id');
                        const newIndex = ui.item.index();

                        const originalIndex = this.template.sections.findIndex(s => s.id === sectionId);
                        if (originalIndex !== -1) {
                            const [movedSection] = this.template.sections.splice(originalIndex, 1);
                            this.template.sections.splice(newIndex, 0, movedSection);
                        }
                        // Alpine should react to this change automatically
                    }
                }).disableSelection();
            }

            // Watch for changes in currentLang to potentially update previews or other elements
            this.$watch('currentLang', (newLang, oldLang) => {
                console.log(`Language switched from ${oldLang} to ${newLang}`);
                // Any specific logic needed when language changes globally can go here
            });

            console.log('Email Template Builder initialized with Alpine.', this.template);
            console.log('Translatable snippets available:', etb_data.translatable_snippets_full);
        },

        switchLang(lang) {
            this.currentLang = lang;
        },

        addSection(type) {
            let newSectionContent = {};
            const defaultContent = { // For text-based properties
                en: `New ${type} content (EN)`,
                pt: `Novo conteúdo de ${type} (PT)`,
                es: `Nuevo contenido de ${type} (ES)`
            };

            switch (type) {
                case 'text':
                    newSectionContent = { ...defaultContent };
                    break;
                case 'image':
                    newSectionContent = {
                        url: { en: '', pt: '', es: '' },
                        alt: { en: '', pt: '', es: '' }
                    };
                    break;
                case 'button':
                    newSectionContent = {
                        text: { ...defaultContent },
                        url: { en: '#', pt: '#', es: '#' },
                        bgColor: '#007bff' // Default button color
                    };
                    break;
                case 'divider':
                    newSectionContent = {}; // No content needed for divider
                    break;
                default:
                    newSectionContent = { ...defaultContent };
            }

            const newSection = {
                id: 'section_' + Date.now() + Math.random().toString(36).substring(2, 9),
                type: type,
                content: newSectionContent
            };
            this.template.sections.push(newSection);
        },

        removeSection(index) {
            if (confirm('Are you sure you want to remove this section?')) {
                this.template.sections.splice(index, 1);
            }
        },

        getSectionTitle(section) {
            const type = section.type;
            // Attempt to get a snippet of text content if available
            let previewText = '';
            if (type === 'text' && section.content && section.content[this.currentLang]) {
                previewText = section.content[this.currentLang].substring(0, 20);
            } else if (type === 'button' && section.content && section.content.text && section.content.text[this.currentLang]) {
                previewText = section.content.text[this.currentLang].substring(0, 20);
            } else if (type === 'image' && section.content && section.content.url && section.content.url[this.currentLang]) {
                previewText = section.content.url[this.currentLang].substring(0, 20);
                 if(previewText.length == 20) previewText += '...';
            }
             if (previewText.length == 20 && type !== 'image') previewText += '...';


            const typeName = this.getSectionTypeTitle(type);
            return previewText ? `${typeName}: ${previewText}` : typeName;
        },

        getSectionTypeTitle(type) {
            switch (type) {
                case 'text': return 'Text';
                case 'image': return 'Image';
                case 'button': return 'Button';
                case 'divider': return 'Divider';
                default: return 'Section';
            }
        },

        getSectionIconClass(type) {
            switch (type) {
                case 'text': return 'dashicons dashicons-editor-paragraph';
                case 'image': return 'dashicons dashicons-format-image';
                case 'button': return 'dashicons dashicons-button';
                case 'divider': return 'dashicons dashicons-minus';
                default: return 'dashicons dashicons-admin-generic';
            }
        },

        setActiveTextarea(textareaElement) {
            this.activeTextarea = textareaElement;
        },

        insertText(textToInsert) {
            if (this.activeTextarea && textToInsert) {
                const start = this.activeTextarea.selectionStart;
                const end = this.activeTextarea.selectionEnd;
                const currentVal = this.activeTextarea.value;
                const newVal = currentVal.substring(0, start) + textToInsert + currentVal.substring(end);

                this.activeTextarea.value = newVal; // Update the textarea/input value directly
                // Dispatch an 'input' event to ensure Alpine.js's x-model picks up the change
                this.activeTextarea.dispatchEvent(new Event('input', { bubbles: true }));

                // Restore focus and cursor position if possible
                this.activeTextarea.focus();
                this.$nextTick(() => {
                    this.activeTextarea.selectionStart = this.activeTextarea.selectionEnd = start + textToInsert.length;
                });
            } else if (!this.activeTextarea && textToInsert) {
                alert('Please click into a text area first to insert content.');
            }
        },

        renderSectionPreview(section) {
            let html = '';
            const sContent = section.content || {}; // Ensure content object exists
            const currentLang = this.currentLang;
            const defaultLang = 'en';

            // Helper to get localized content, falling back to defaultLang
            const getLocalized = (obj, key) => {
                if (obj && typeof obj === 'object' && obj[key] && typeof obj[key] === 'object') {
                    return obj[key][currentLang] || obj[key][defaultLang] || '';
                } else if (obj && typeof obj === 'object' && obj[currentLang]) { // For direct text content like in old text sections
                     return obj[currentLang] || obj[defaultLang] || '';
                }
                return obj || ''; // Fallback for non-localized or simple string content
            };

            // Helper to process snippets and variables for a given text string
            const processText = (text) => {
                if (typeof text !== 'string') text = '';
                // Snippets
                text = text.replace(/\{\{snippet:([a-zA-Z0-9_]+)\}\}/g, (match, snippetKey) => {
                    return (etb_data.translatable_snippets_full && etb_data.translatable_snippets_full[snippetKey] && etb_data.translatable_snippets_full[snippetKey][currentLang])
                           ? etb_data.translatable_snippets_full[snippetKey][currentLang]
                           : match;
                });
                // Variables
                text = text.replace(/\{\{([a-zA-Z0-9_]+)\}\}/g, (match) => {
                    const DUMMY_TEXT_COLOR = '#888';
                    return `<span style="color: ${DUMMY_TEXT_COLOR}; font-family: monospace; background-color: #f0f0f0; padding: 1px 3px; border-radius: 3px;">${match.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</span>`;
                });
                return text;
            };

            const outerTableStyle = "border-bottom: 1px dashed #eee;"; // Mimics PHP export style for consistency
            const textCellStyle = "padding: 10px; font-family: Arial, sans-serif; font-size: 14px; line-height: 1.5; color: #333;";


            switch (section.type) {
                case 'text':
                    const text = processText(getLocalized(sContent, currentLang)); // sContent itself is the multilingual object for text
                    html = `<table width="100%" border="0" cellpadding="0" cellspacing="0" style="${outerTableStyle}"><tr><td style="${textCellStyle}">${text.replace(/\n/g, '<br>\n')}</td></tr></table>`;
                    break;
                case 'image':
                    const imageUrl = getLocalized(sContent.url, currentLang); // sContent.url is the multilingual object
                    const altText = processText(getLocalized(sContent.alt, currentLang)); // sContent.alt is multilingual
                    if (imageUrl) {
                        html = `<table width="100%" border="0" cellpadding="0" cellspacing="0" style="${outerTableStyle}"><tr><td align="center" style="padding:10px;"><img src="${imageUrl}" alt="${altText}" style="display:block; max-width:100%; height:auto; border:0;" /></td></tr></table>`;
                    } else {
                        html = `<table width="100%" border="0" cellpadding="0" cellspacing="0" style="${outerTableStyle}"><tr><td style="${textCellStyle} text-align:center; color:#aaa;">[Image: No URL provided]</td></tr></table>`;
                    }
                    break;
                case 'button':
                    const buttonText = processText(getLocalized(sContent.text, currentLang));
                    const buttonUrl = getLocalized(sContent.url, currentLang) || '#';
                    const bgColor = sContent.bgColor || '#007bff';
                    const buttonTdStyle = `background-color:${bgColor}; border-radius:5px; padding:10px 20px; text-align:center;`;
                    const buttonLinkStyle = "font-family: Arial, sans-serif; font-size: 16px; color: #ffffff; text-decoration: none; display:inline-block;";
                    html = `<table width="100%" border="0" cellpadding="0" cellspacing="0" style="${outerTableStyle} text-align:center;"><tr><td align="center" style="padding:10px;"><table border="0" cellpadding="0" cellspacing="0"><tr><td style="${buttonTdStyle}"><a href="${buttonUrl}" target="_blank" style="${buttonLinkStyle}">${buttonText}</a></td></tr></table></td></tr></table>`;
                    break;
                case 'divider':
                    const dividerStyle = "border-top:1px solid #dddddd; height:1px; margin:10px 0;";
                    html = `<table width="100%" border="0" cellpadding="0" cellspacing="0" style="${outerTableStyle}"><tr><td style="padding:10px;"><div style="${dividerStyle}"></div></td></tr></table>`;
                    break;
                default:
                    html = `<table width="100%" border="0" cellpadding="0" cellspacing="0" style="${outerTableStyle}"><tr><td style="${textCellStyle} color:red;">Unsupported section type: ${section.type}</td></tr></table>`;
            }
            return html;
        },

        copyToClipboard(text) {
            if (!navigator.clipboard) {
                // Fallback for older browsers
                const textarea = document.createElement('textarea');
                textarea.value = text;
                document.body.appendChild(textarea);
                textarea.select();
                try {
                    document.execCommand('copy');
                    alert('Copied to clipboard (fallback): ' + text);
                } catch (err) {
                    alert('Failed to copy (fallback).');
                }
                document.body.removeChild(textarea);
                return;
            }
            navigator.clipboard.writeText(text).then(() => {
                alert('Copied to clipboard: ' + text);
            }).catch(err => {
                console.error('Failed to copy: ', err);
                alert('Failed to copy.');
            });
        },

        // This computed property will generate the full HTML for the preview iframe
        get fullPreviewHTML() {
            let bodyContent = this.template.sections.map(section => this.renderSectionPreview(section)).join('');
            if (this.template.sections.length === 0) {
                bodyContent = '<p style="text-align:center; color:#888; padding-top: 50px;">Preview will appear here as you add sections.</p>';
            }

            return `
                <!DOCTYPE html>
                <html lang="${this.currentLang}">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Email Preview</title>
                    <style>
                        body { margin: 0; padding: 0; background-color: #f7f7f7; font-family: Arial, sans-serif; }
                        .email-container { max-width: 600px; margin: 20px auto; background-color: #ffffff; border: 1px solid #ddd; box-shadow: 0 0 10px rgba(0,0,0,0.05); }
                        /* Add more global email styles here if needed */
                    </style>
                </head>
                <body>
                    <div class="email-container">
                        ${bodyContent}
                    </div>
                </body>
                </html>
            `;
        },

        updatePreviewIframe() {
            const iframe = this.$refs.previewIframe;
            if (iframe) {
                iframe.srcdoc = this.fullPreviewHTML;
            }
        },

        init() {
            // ... (rest of init)
            const sectionsList = document.getElementById('etb-sections-list');
            if (sectionsList) {
                $(sectionsList).sortable({
                    handle: '.etb-section-header',
                    update: (event, ui) => {
                        const sectionId = ui.item.data('id');
                        const newIndex = ui.item.index();

                        const originalIndex = this.template.sections.findIndex(s => s.id === sectionId);
                        if (originalIndex !== -1) {
                            const [movedSection] = this.template.sections.splice(originalIndex, 1);
                            this.template.sections.splice(newIndex, 0, movedSection);
                        }
                    }
                }).disableSelection();
            }

            this.$watch('currentLang', (newLang, oldLang) => {
                console.log(`Language switched from ${oldLang} to ${newLang}`);
                this.updatePreviewIframe();
            });

            // Watch for any changes in template sections to update the iframe
            // Deep watch template.sections array for changes in content
             this.$watch('template.sections', (newSections, oldSections) => {
                this.updatePreviewIframe();
            }, { deep: true });


            console.log('Email Template Builder initialized with Alpine.', this.template);
            console.log('Translatable snippets available:', etb_data.translatable_snippets_full);

            this.$nextTick(() => {
                this.updatePreviewIframe(); // Initial iframe content
            });
        },

        isLoading: false, // To provide UI feedback during save

        saveTemplate() {
            if (!this.template.title.trim()) {
                alert('Please enter a template name.');
                return;
            }
            this.isLoading = true;

            const path = this.template.id ? `/wp/v2/email_template/${this.template.id}` : '/wp/v2/email_template';
            const method = this.template.id ? 'PUT' : 'POST';

            const dataToSave = {
                title: this.template.title,
                status: 'publish', // Or allow user to choose draft/publish
                meta: {
                    _template_structure: JSON.stringify(this.template.sections)
                }
            };

            wp.apiFetch({
                path: path,
                method: method,
                data: dataToSave,
                headers: {
                    'X-WP-Nonce': etb_data.nonce // This nonce is for the REST API
                }
            })
            .then(response => {
                console.log('Saved response:', response);
                this.isLoading = false;
                alert('Template saved successfully!');

                if (response.id && !this.template.id) {
                    this.template.id = response.id;
                    // Update URL to reflect editing this template ID, so refresh works
                    const newUrl = new URL(window.location.href);
                    newUrl.searchParams.set('action', 'edit');
                    newUrl.searchParams.set('template_id', response.id);
                    window.history.pushState({path:newUrl.href}, '', newUrl.href);
                }
                 // After saving, we might want to refresh the list if the user goes back
                // or update the current view if fields like "last modified" are shown.
            })
            .catch(error => {
                this.isLoading = false;
                console.error('Error saving template:', error);
                let errorMessage = 'Error saving template.';
                if (error.message) {
                    errorMessage += ' ' + error.message;
                }
                if (error.code) {
                     errorMessage += ` (Code: ${error.code})`;
                }
                alert(errorMessage);
            });
        },

        exportCurrentLanguageHTML() {
            if (!this.template.id) {
                alert('Please save the template before exporting.');
                return;
            }
            // Construct the URL for export
            // We'll use admin_url for the base and add our own query parameters for a custom action.
            // A dedicated admin action hook (admin_action_{action_name}) is cleaner than admin-post.
            const exportUrl = new URL(etb_data.base_url.replace('/wp-json', '/wp-admin/admin.php')); // A bit hacky to get admin_url like this
            exportUrl.searchParams.set('action', 'etb_export_template_html');
            exportUrl.searchParams.set('template_id', this.template.id);
            exportUrl.searchParams.set('lang', this.currentLang);
            exportUrl.searchParams.set('_wpnonce', etb_data.export_nonce); // We'll need to add this nonce

            window.open(exportUrl.toString(), '_blank');
        },

        resetToLastSaved() {
            if (confirm('Are you sure you want to reset all changes to the last saved version?')) {
                this.template = JSON.parse(JSON.stringify(this.initialDataSnapshot));
                // After resetting, the preview needs to be updated
                this.$nextTick(() => {
                    this.updatePreviewIframe();
                });
                alert('Template has been reset to the last saved version.');
            }
        }
    }));

    // Handle delete links in the list table (if present on the page)
    // This part is outside the Alpine component, runs once on DOMContentLoaded
    document.addEventListener('DOMContentLoaded', () => {
        const listTableForm = document.getElementById('etb-list-table-form');
        if (listTableForm) {
            listTableForm.addEventListener('click', function(event) {
                if (event.target.classList.contains('etb-delete-template')) {
                    event.preventDefault();
                    if (confirm('Are you sure you want to delete this template? This action cannot be undone.')) {
                        const templateId = event.target.dataset.id;
                        const nonce = event.target.dataset.nonce; // This is the etb_delete_template_ nonce

                        // We need the main REST API nonce for the header
                        const restNonce = etb_data && etb_data.nonce ? etb_data.nonce : '';
                        if (!restNonce) {
                            alert('Security error: REST Nonce not found. Cannot delete template.');
                            return;
                        }

                        // Note: The nonce on the link (etb_delete_template_) is for potential direct GET deletion or AJAX action handler.
                        // For wp.apiFetch DELETE, the X-WP-Nonce header (etb_data.nonce) is the primary one.
                        // We can still verify `nonce` if we had a custom AJAX action, but for REST API, it's less direct.
                        // However, it's good practice to have it for non-JS fallback or if we decide to use an AJAX action.

                        wp.apiFetch({
                            path: `/wp/v2/email_template/${templateId}`,
                            method: 'DELETE',
                            headers: {
                                'X-WP-Nonce': restNonce
                            },
                            // data: { // For DELETE, data is often not needed, but if force is required:
                            //    force: true // To bypass trash
                            // }
                        })
                        .then(response => {
                            // console.log('Delete response:', response);
                            alert('Template deleted successfully.');
                            // Remove row from table or reload
                            // event.target.closest('tr').remove(); // Simple removal
                            location.reload(); // Easiest way to refresh list and pagination
                        })
                        .catch(error => {
                            console.error('Error deleting template:', error);
                            let errorMessage = 'Error deleting template.';
                             if (error.message) {
                                errorMessage += ' ' + error.message;
                            }
                            if (error.code) {
                                errorMessage += ` (Code: ${error.code})`;
                            }
                            alert(errorMessage);
                        });
                    }
                }
            });
        }
    });
});
