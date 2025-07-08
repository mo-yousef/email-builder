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
            const defaultMultilingualText = (baseText = `New ${type}`) => ({
                en: `${baseText} (EN)`,
                pt: `${baseText} (PT)`,
                es: `${baseText} (ES)`
            });

            switch (type) {
                case 'greeting_text': // Specialized
                    newSectionContent = defaultMultilingualText('Good day {{name}},');
                    break;
                case 'main_paragraph': // Specialized
                    newSectionContent = defaultMultilingualText('This is the main paragraph.');
                    break;
                case 'trading_schedule': // Specialized container for new "Holiday Notification" template
                    newSectionContent = {
                        date_header_1: defaultMultilingualText('Thursday - MM.DD.YYYY'),
                        rows_1: [], // Array for the first day's rows
                        date_header_2: defaultMultilingualText('Friday - MM.DD.YYYY'),
                        rows_2: []  // Array for the second day's rows
                    };
                    break;
                // Note: 'trading_row_item' is not added directly via addSection, but within a 'trading_schedule'
                case 'closing_text': // Specialized
                    newSectionContent = defaultMultilingualText('Regards,\nThe {{company_name}} Team');
                    break;
                // Generic types from previous implementation (can be kept or removed if not used by master templates)
                case 'text':
                    newSectionContent = defaultMultilingualText();
                    break;
                case 'image':
                    newSectionContent = {
                        url: { en: '', pt: '', es: '' },
                        alt: defaultMultilingualText('')
                    };
                    break;
                case 'button':
                    newSectionContent = {
                        text: defaultMultilingualText('Button Text'),
                        url: { en: '#', pt: '#', es: '#' },
                        bgColor: '#007bff'
                    };
                    break;
                case 'divider':
                    newSectionContent = {}; // No content needed
                    break;
                default:
                    console.warn(`Attempting to add unknown section type: ${type}`);
                    return; // Don't add unknown types
            }

            const newSection = {
                id: 'section_' + Date.now() + Math.random().toString(36).substring(2, 9),
                type: type,
                content: newSectionContent
            };
            this.template.sections.push(newSection);
        },

        // Methods for managing trading_row_items within a trading_schedule section
        addTradingRow(scheduleSectionIndex, dayKey) { // dayKey would be 'rows_1' or 'rows_2'
            if (this.template.sections[scheduleSectionIndex] && this.template.sections[scheduleSectionIndex].type === 'trading_schedule') {
                const newRow = {
                    id: 'trading_row_' + Date.now() + Math.random().toString(36).substring(2,7), // Unique ID for the row
                    instrument: { en: 'INSTRUMENT', pt: 'INSTRUMENTO', es: 'INSTRUMENTO' },
                    time_status: { en: 'Market Hours', pt: 'Horário de Mercado', es: 'Horario de Mercado' }
                };
                this.template.sections[scheduleSectionIndex].content[dayKey].push(newRow);
            }
        },
        removeTradingRow(scheduleSectionIndex, dayKey, rowIndex) {
            if (this.template.sections[scheduleSectionIndex] && this.template.sections[scheduleSectionIndex].content[dayKey]) {
                this.template.sections[scheduleSectionIndex].content[dayKey].splice(rowIndex, 1);
            }
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
                case 'text': return 'Text Block';
                case 'image': return 'Image Block';
                case 'button': return 'Button Block';
                case 'divider': return 'Divider';
                case 'greeting_text': return 'Greeting Text';
                case 'main_paragraph': return 'Main Paragraph';
                case 'trading_schedule': return 'Trading Schedule';
                case 'closing_text': return 'Closing Text';
                default: return 'Section';
            }
        },

        getSectionIconClass(type) {
            switch (type) {
                case 'text': return 'dashicons dashicons-editor-paragraph';
                case 'image': return 'dashicons dashicons-format-image';
                case 'button': return 'dashicons dashicons-button';
                case 'divider': return 'dashicons dashicons-minus';
                case 'greeting_text': return 'dashicons dashicons-testimonial'; // Example icon
                case 'main_paragraph': return 'dashicons dashicons-editor-alignleft'; // Example icon
                case 'trading_schedule': return 'dashicons dashicons-calendar-alt'; // Example icon
                case 'closing_text': return 'dashicons dashicons-edit-page'; // Example icon
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

        /**
         * Renders the HTML content snippet for a given section for the live preview.
         * This function is now geared towards returning minimal HTML for injection into a master layout.
         */
        renderSectionContentPreview(section) {
            let contentHtml = '';
            const sContent = section.content || {};
            const currentLang = this.currentLang;
            const defaultLang = 'en';

            const getLocalized = (fieldData, fieldKey) => { // fieldKey is 'text', 'url', 'alt' etc.
                if (fieldData && typeof fieldData === 'object') {
                    return fieldData[currentLang] || fieldData[defaultLang] || '';
                }
                return fieldData || ''; // For non-multilingual or direct values like bgColor
            };

            const processText = (text) => { // Same as before
                if (typeof text !== 'string') text = '';
                text = text.replace(/\{\{snippet:([a-zA-Z0-9_]+)\}\}/g, (match, snippetKey) => {
                    return (etb_data.translatable_snippets_full && etb_data.translatable_snippets_full[snippetKey] && etb_data.translatable_snippets_full[snippetKey][currentLang])
                           ? etb_data.translatable_snippets_full[snippetKey][currentLang]
                           : match;
                });
                text = text.replace(/\{\{([a-zA-Z0-9_]+)\}\}/g, (match) => {
                    const DUMMY_TEXT_COLOR = '#888';
                    return `<span style="color: ${DUMMY_TEXT_COLOR}; font-family: monospace; background-color: #f0f0f0; padding: 1px 3px; border-radius: 3px;">${match.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</span>`;
                });
                return text;
            };

            switch (section.type) {
                case 'greeting_text':
                case 'main_paragraph':
                case 'closing_text':
                    // These sections output processed text that will be wrapped by <p> or other tags in the master HTML.
                    // The master HTML structure for these placeholders should be simple, e.g., inside a <td> or <p>.
                    // We directly return the processed text, and nl2br will be applied if it's inside a <p> in the master.
                    contentHtml = processText(getLocalized(sContent, currentLang)).replace(/\n/g, '<br>\n');
                    break;
                // trading_schedule itself doesn't render directly, its sub-parts do.
                // trading_row_item is rendered by fullPreviewHTML when iterating rows.
                // Generic types (if kept for other templates or direct use):
                case 'text':
                    contentHtml = processText(getLocalized(sContent, currentLang)).replace(/\n/g, '<br>\n');
                    // This would be injected into a simple <td> in a generic block wrapper if used.
                    break;
                case 'image':
                    const imageUrl = getLocalized(sContent.url, currentLang);
                    const altText = processText(getLocalized(sContent.alt, currentLang));
                    if (imageUrl) {
                        contentHtml = `<img src="${imageUrl}" alt="${altText}" style="display:block; max-width:100%; height:auto; border:0;" />`;
                    } else {
                        contentHtml = `[Image: No URL provided]`;
                    }
                    break;
                case 'button':
                    const buttonText = processText(getLocalized(sContent.text, currentLang));
                    const buttonUrl = getLocalized(sContent.url, currentLang) || '#';
                    const bgColor = sContent.bgColor || '#007bff';
                    // This generates the button HTML, assuming it's placed within a centered td in the master structure for generic buttons.
                    const buttonTdStyle = `background-color:${bgColor}; border-radius:5px; padding:10px 20px; text-align:center;`;
                    const buttonLinkStyle = "font-family: Arial, sans-serif; font-size: 16px; color: #ffffff; text-decoration: none; display:inline-block;";
                    contentHtml = `<table border="0" cellpadding="0" cellspacing="0" role="presentation" style="margin:0 auto;"><tr><td style="${buttonTdStyle}"><a href="${buttonUrl}" target="_blank" style="${buttonLinkStyle}">${buttonText}</a></td></tr></table>`;
                    break;
                case 'divider':
                    contentHtml = '<div style="border-top:1px solid #dddddd; height:1px; margin:10px 0;"></div>';
                    break;
                // No default needed as fullPreviewHTML will decide what to do with sections.
            }
            return contentHtml;
        },

        /**
         * Renders a single trading row item into its HTML string (<tr>...</tr>).
         */
        renderTradingRowItemPreview(item, lang) {
            // Simplified processText for this specific context
            const processSimpleText = (text) => {
                 if (typeof text !== 'string') text = '';
                // Variables only for preview simplicity, snippets might be too complex here
                return text.replace(/\{\{([a-zA-Z0-9_]+)\}\}/g, (match) => {
                    const DUMMY_TEXT_COLOR = '#888';
                    return `<span style="color: ${DUMMY_TEXT_COLOR}; font-family: monospace; background-color: #f0f0f0; padding: 1px 3px; border-radius: 3px;">${match.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</span>`;
                });
            };
            const instrument = processSimpleText(item.instrument[lang] || item.instrument['en'] || '');
            const time_status = processSimpleText(item.time_status[lang] || item.time_status['en'] || '');
            // This structure must match the one in holiday-notification-master.html.php's trading rows
            return `<tr><td width="50%" style="vertical-align: top; padding: 10px; word-break: break-word; font-family: 'Montserrat', sans-serif; font-weight: 500; color: #475467; font-size: 14px;"><strong>${instrument}</strong></td><td width="50%" style="vertical-align: top; padding: 10px; word-break: break-word; font-family: 'Montserrat', sans-serif; font-weight: 500; color: #475467; font-size: 14px;">${time_status}</td></tr>`;
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
            // IMPORTANT: This masterLayoutTemplateJS needs to be a JS string version of the holiday-notification-master.html.php
            // For brevity here, I'm using a very simplified version. In reality, this would be large.
            // It must contain the exact same <!-- ETB_... --> placeholders.
            let masterLayoutTemplateJS = `
                <!DOCTYPE html>
                <html lang="${this.currentLang}">
                <head>
                    <meta charset="UTF-8">
                    <title><!-- ETB_TEMPLATE_TITLE --></title>
                    <style>
                        body { margin: 0; padding: 20px; font-family: Arial, sans-serif; background-color: #f0f0f0; }
                        .email-content-wrapper { background-color: #ffffff; padding: 20px; max-width: 600px; margin: 0 auto; border: 1px solid #ddd;}
                        .placeholder-style { padding: 10px; margin-bottom:10px; border: 1px dashed #ccc; background: #f9f9f9;}
                        .trading-row td { padding: 5px; border-bottom: 1px solid #eee; }
                    </style>
                </head>
                <body>
                    <div class="email-content-wrapper">
                        <div class="placeholder-style"><!-- ETB_GREETING_TEXT_START -->Default Greeting Preview<!-- ETB_GREETING_TEXT_END --></div>
                        <div class="placeholder-style"><!-- ETB_MAIN_PARAGRAPH_START -->Default Main Paragraph Preview<!-- ETB_MAIN_PARAGRAPH_END --></div>

                        <div class="placeholder-style">
                           <h3><!-- ETB_SCHEDULE_DATE_HEADER_1_START -->Thursday Schedule Header Preview<!-- ETB_SCHEDULE_DATE_HEADER_1_END --></h3>
                           <table><tbody id="etb-rows-day1-preview"><!-- ETB_TRADING_ROWS_THURSDAY_START --><!-- ETB_TRADING_ROWS_THURSDAY_END --></tbody></table>
                        </div>
                        <div class="placeholder-style">
                           <h3><!-- ETB_SCHEDULE_DATE_HEADER_2_START -->Friday Schedule Header Preview<!-- ETB_SCHEDULE_DATE_HEADER_2_END --></h3>
                           <table><tbody id="etb-rows-day2-preview"><!-- ETB_TRADING_ROWS_FRIDAY_START --><!-- ETB_TRADING_ROWS_FRIDAY_END --></tbody></table>
                        </div>

                        <div class="placeholder-style"><!-- ETB_CLOSING_TEXT_START -->Default Closing Preview<!-- ETB_CLOSING_TEXT_END --></div>
                        <div class="placeholder-style"><!-- ETB_FOOTER_CONTENT_START -->Static Footer Preview (not editable via current sections)<!-- ETB_FOOTER_CONTENT_END --></div>
                    </div>
                </body>
                </html>
            `;

            let finalHtml = masterLayoutTemplateJS;
            finalHtml = finalHtml.replace('<!-- ETB_TEMPLATE_TITLE -->', this.template.title ? this.escapeHtml(this.template.title) : 'Email Preview');

            const getLocalized = (fieldData, langKey = this.currentLang) => {
                 if (fieldData && typeof fieldData === 'object') {
                    return fieldData[langKey] || fieldData['en'] || '';
                }
                return fieldData || '';
            };
            const processTextForPreview = (text) => { // Simplified for preview, full processing in PHP export
                if (typeof text !== 'string') return '';
                return text.replace(/\{\{snippet:([a-zA-Z0-9_]+)\}\}/g, (match, snippetKey) => {
                    return (etb_data.translatable_snippets_full && etb_data.translatable_snippets_full[snippetKey] && etb_data.translatable_snippets_full[snippetKey][this.currentLang])
                           ? `<em>${etb_data.translatable_snippets_full[snippetKey][this.currentLang]}</em>` // Show snippet resolved
                           : match;
                }).replace(/\{\{([a-zA-Z0-9_]+)\}\}/g, (match) => {
                    return `<span style="color: #888; font-family: monospace; background-color: #f0f0f0; padding: 1px 3px; border-radius: 3px;">${this.escapeHtml(match)}</span>`;
                }).replace(/\n/g, '<br>\n');
            };

            this.template.sections.forEach(section => {
                let contentToInject = '';
                switch (section.type) {
                    case 'greeting_text':
                        contentToInject = processTextForPreview(getLocalized(section.content));
                        finalHtml = this.replacePlaceholderBlock(finalHtml, 'ETB_GREETING_TEXT', contentToInject);
                        break;
                    case 'main_paragraph':
                        contentToInject = processTextForPreview(getLocalized(section.content));
                        finalHtml = this.replacePlaceholderBlock(finalHtml, 'ETB_MAIN_PARAGRAPH', contentToInject);
                        break;
                    case 'closing_text':
                        contentToInject = processTextForPreview(getLocalized(section.content));
                        finalHtml = this.replacePlaceholderBlock(finalHtml, 'ETB_CLOSING_TEXT', contentToInject);
                        break;
                    case 'trading_schedule':
                        const header1 = processTextForPreview(getLocalized(section.content.date_header_1));
                        finalHtml = this.replacePlaceholderBlock(finalHtml, 'ETB_SCHEDULE_DATE_HEADER_1', header1);

                        let rows1Html = '';
                        (section.content.rows_1 || []).forEach(row => {
                            rows1Html += this.renderTradingRowItemPreview(row, this.currentLang);
                        });
                        finalHtml = this.replacePlaceholderBlock(finalHtml, 'ETB_TRADING_ROWS_THURSDAY', rows1Html);

                        const header2 = processTextForPreview(getLocalized(section.content.date_header_2));
                        finalHtml = this.replacePlaceholderBlock(finalHtml, 'ETB_SCHEDULE_DATE_HEADER_2', header2);

                        let rows2Html = '';
                        (section.content.rows_2 || []).forEach(row => {
                            rows2Html += this.renderTradingRowItemPreview(row, this.currentLang);
                        });
                        finalHtml = this.replacePlaceholderBlock(finalHtml, 'ETB_TRADING_ROWS_FRIDAY', rows2Html);
                        break;
                    // Generic sections might not be used with this master template, or would need different placeholders
                }
            });

            // Clean up any placeholders that didn't get content
            finalHtml = finalHtml.replace(/<!-- ETB_([A-Z0-9_]+)_START -->.*?<!-- ETB_\1_END -->/gs, '<!-- Placeholder \1 not filled -->');
            finalHtml = finalHtml.replace(/<!-- ETB_([A-Z0-9_]+) -->/g, '<!-- Placeholder \1 not filled -->');


            return finalHtml;
        },

        // Helper to escape HTML for display in preview (e.g. for variable placeholders)
        escapeHtml(text) {
            if (typeof text !== 'string') return '';
            return text.replace(/[&<>"']/g, function (match) {
                return {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#39;'
                }[match];
            });
        },

        // Helper for replacing placeholder blocks
        replacePlaceholderBlock(masterHtml, placeholderBaseName, content) {
            const startTag = `<!-- ${placeholderBaseName}_START -->`;
            const endTag = `<!-- ${placeholderBaseName}_END -->`;
            const regex = new RegExp(this.escapeRegExp(startTag) + '([\\s\\S]*?)' + this.escapeRegExp(endTag), 'g');
            return masterHtml.replace(regex, content ? (startTag + content + endTag) : `<!-- ${placeholderBaseName} is empty -->`);
        },
        escapeRegExp(string) {
            return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); // $& means the whole matched string
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
