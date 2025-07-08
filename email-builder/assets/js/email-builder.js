// Email Template Builder - Main JavaScript File
(function($) {
    'use strict';

    let currentTemplateId = null; // Stores the ID of the template being edited
    let isDirty = false; // Flag to track unsaved changes

    // Store the initial HTML template provided by the user.
    // This will be populated by wp_localize_script.
    let baseTemplateHtml = '';
    let currentPreviews = { en: '', pt: '', es: '' }; // Store current HTML state for each language
    let translations = {}; // Placeholder for the translation dictionary

    // --- Utility Functions ---
    function setDirty(dirty) {
        isDirty = dirty;
        $('#etb-save-template-button').prop('disabled', !dirty);
        $('#etb-reset-template-button').prop('disabled', !dirty);
    }

    function updatePreview(lang, htmlContent) {
        const iframe = $(`#etb-iframe-${lang}`)[0];
        if (iframe) {
            const doc = iframe.contentWindow.document;
            doc.open();
            doc.write(htmlContent);
            doc.close();
            currentPreviews[lang] = htmlContent; // Store the updated HTML
            // console.log(`Preview updated for ${lang}`);
        } else {
            console.error(`Iframe for language ${lang} not found.`);
        }
    }

    // Simplified translation function
    function translate(text, targetLang, sectionId = null) {
        // Try section-specific key first if provided (e.g., for disambiguation)
        if (sectionId && translations[sectionId] && translations[sectionId][text] && translations[sectionId][text][targetLang.toUpperCase()]) {
            return translations[sectionId][text][targetLang.toUpperCase()];
        }
        // Try general text key
        if (translations[text] && translations[text][targetLang.toUpperCase()]) {
            return translations[text][targetLang.toUpperCase()];
        }
        // Fallback for dynamic variables: do not translate
        if (text.match(/\{\{[^{}]+\}\}/g)) { // Check for {{variable}}
            return text;
        }
        // Fallback: if no translation, return original text for EN, or marked text for others
        if (targetLang.toLowerCase() === 'en') {
            return text;
        }
        // console.warn(`No translation for "${text}" to ${targetLang.toUpperCase()}`);
        return `[${targetLang.toUpperCase()}] ${text}`;
    }

    // Helper to get content from a section in an HTML string
    function extractSectionContent(htmlString, sectionId, sectionType, lang = 'en') {
        if (!htmlString) {
            return sectionType === 'image' ? { src: '', alt: '' } : '';
        }
        let tempDom = $('<div>').html(htmlString);
        let sectionEl = tempDom.find(`[data-etb-section-id="${sectionId}"]`);

        if (!sectionEl.length) {
            return sectionType === 'image' ? { src: '', alt: '' } : '';
        }

        if (sectionType === 'image') {
            let img = sectionEl.find('img');
            return {
                src: img.attr('src') || '',
                alt: img.attr('alt') || ''
                // For multilingual images, if src can change per lang, this needs adjustment
            };
        } else { // text, textarea
            return sectionEl.text().trim();
        }
    }


    // --- Core Template Handling ---
    function loadBaseStructure(htmlForParsing, initialValues = null) {
        if (!htmlForParsing || htmlForParsing.length === 0) {
            console.warn("Initial template HTML not provided or empty for parsing.");
            baseTemplateHtml = '<!DOCTYPE html><html><head><title>Email</title><style>body{font-family:Arial,sans-serif;}</style></head><body><div data-etb-section-id="greeting" data-etb-type="text"><p>Hello {{name}}!</p></div><div data-etb-section-id="body" data-etb-type="textarea"><p>This is the default body content.</p></div></body></html>';
            currentPreviews.en = baseTemplateHtml;
        } else {
            baseTemplateHtml = htmlForParsing; // Keep the original structure for reference
            currentPreviews.en = htmlForParsing; // EN preview starts with the base
        }

        // Generate other language previews from EN version (initial load)
        // This is a very naive initial translation - proper translation will be more complex
        currentPreviews.pt = applyTranslationsToHtml(currentPreviews.en, 'pt');
        currentPreviews.es = applyTranslationsToHtml(currentPreviews.en, 'es');

        updatePreview('en', currentPreviews.en);
        updatePreview('pt', currentPreviews.pt);
        updatePreview('es', currentPreviews.es);

        parseTemplateForControls(currentPreviews.en, initialValues); // Parse EN version to build controls
        setDirty(false);
    }

    // Function to apply translations to an HTML string
    // This is a simplified version. Real-world scenario might involve more robust parsing.
    function applyTranslationsToHtml(htmlString, targetLang) {
        let translatedHtml = htmlString;
        // Example: find elements with data-translatable attribute or specific text nodes
        // This needs to be more sophisticated based on how translatable parts are marked in your HTML.
        // For now, let's assume we find text nodes and try to translate them.
        // This is highly conceptual and would need a proper DOM parser on the string if complex.

        // A more realistic approach: iterate over known translatable keys from controls
        $('#etb-sortable-items-container .etb-translatable-control').each(function() {
            const control = $(this);
            const originalText = control.data('original-en-text'); // Assume this is stored
            const sectionId = control.closest('.etb-sortable-section').data('etb-section-id');
            const targetSelector = `[data-etb-section-id="${sectionId}"]`; // Simplified selector

            if (originalText) {
                const translatedText = translate(originalText, targetLang);
                // This is tricky: how to replace only the correct text in the HTML string?
                // Using a regex might be too fragile. For now, this is a placeholder.
                // A better way is to update a DOM representation and then serialize it.
                // Or, each control update directly modifies language-specific DOMs in iframes.
            }
        });
        // For now, let's just return the htmlString and handle translation on control change
        return htmlString;
    }

    function parseTemplateForControls(htmlContent, initialValues) {
        console.log("Parsing template for controls...");
        const controlsContainer = $('#etb-sortable-items-container');
        controlsContainer.empty(); // Clear existing controls

        // Define editable sections based on data attributes in the HTML
        // Example: <div data-etb-section-id="greeting" data-etb-type="text" data-etb-label="Greeting Message">...</div>
        //          <div data-etb-section-id="main_image" data-etb-type="image" data-etb-label="Main Image"><img></div>

        let tempDom = $('<div>').html(htmlContent); // Create a temporary DOM to parse

        tempDom.find('[data-etb-section-id]').each(function(index) {
            const section = $(this);
            const sectionId = section.data('etb-section-id');
            const sectionType = section.data('etb-type') || 'text'; // Default to text
            const sectionLabel = section.data('etb-label') || sectionId.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            const isTranslatable = section.data('etb-translatable') !== 'false'; // Translatable by default unless explicitly false

            let controlHtml = `<div class="etb-sortable-section" data-etb-section-id="${sectionId}" data-etb-section-type="${sectionType}" data-etb-translatable="${isTranslatable}"><h5>${sectionLabel}</h5>`;

            const enValue = extractSectionContent(htmlContent, sectionId, sectionType, 'en');
            let ptValue = '', esValue = '';

            if (isTranslatable) {
                if (initialValues && initialValues[sectionId]) { // If loading a saved template
                    ptValue = initialValues[sectionId].pt || translate(enValue, 'pt', sectionId);
                    esValue = initialValues[sectionId].es || translate(enValue, 'es', sectionId);
                } else { // New template or no specific saved values for PT/ES
                    ptValue = translate(enValue, 'pt', sectionId);
                    esValue = translate(enValue, 'es', sectionId);
                }
            }


            if (sectionType === 'text' || sectionType === 'textarea') {
                const inputTag = sectionType === 'textarea' ? 'textarea' : 'input type="text"';
                const enInput = `<${inputTag} id="ctrl_${sectionId}_en" class="etb-content-control etb-lang-en" data-lang="en">${sectionType === 'textarea' ? enValue : ''}</${inputTag}>`;
                if(sectionType === 'text') $(document).ready(() => $(`#ctrl_${sectionId}_en`).val(enValue));


                controlHtml += `<div class="etb-control-group">
                                <label for="ctrl_${sectionId}_en">EN:</label>
                                ${enInput}
                            </div>`;
                if (isTranslatable) {
                    const ptInput = `<${inputTag} id="ctrl_${sectionId}_pt" class="etb-content-control etb-lang-pt" data-lang="pt">${sectionType === 'textarea' ? ptValue : ''}</${inputTag}>`;
                    if(sectionType === 'text') $(document).ready(() => $(`#ctrl_${sectionId}_pt`).val(ptValue));

                    const esInput = `<${inputTag} id="ctrl_${sectionId}_es" class="etb-content-control etb-lang-es" data-lang="es">${sectionType === 'textarea' ? esValue : ''}</${inputTag}>`;
                    if(sectionType === 'text') $(document).ready(() => $(`#ctrl_${sectionId}_es`).val(esValue));

                    controlHtml += `<div class="etb-control-group">
                                    <label for="ctrl_${sectionId}_pt">PT:</label>
                                    ${ptInput}
                                </div>
                                <div class="etb-control-group">
                                    <label for="ctrl_${sectionId}_es">ES:</label>
                                    ${esInput}
                                </div>`;
                }
            } else if (sectionType === 'image') {
                const enImgDetails = extractSectionContent(htmlContent, sectionId, sectionType, 'en'); // Expects {src: '', alt:''}
                let ptAlt = '', esAlt = '';

                if (isTranslatable) {
                     if (initialValues && initialValues[sectionId] && initialValues[sectionId].alt_pt) {
                        ptAlt = initialValues[sectionId].alt_pt;
                    } else {
                        ptAlt = translate(enImgDetails.alt, 'pt', sectionId + '_alt');
                    }
                    if (initialValues && initialValues[sectionId] && initialValues[sectionId].alt_es) {
                        esAlt = initialValues[sectionId].alt_es;
                    } else {
                        esAlt = translate(enImgDetails.alt, 'es', sectionId + '_alt');
                    }
                }

                controlHtml += `<div class="etb-control-group">
                                <label for="ctrl_${sectionId}_src">Image URL:</label>
                                <input type="text" id="ctrl_${sectionId}_src" class="etb-image-control etb-image-src" value="${enImgDetails.src}" data-target-attr="src" />
                                <label for="ctrl_${sectionId}_alt_en">Alt Text (EN):</label>
                                <input type="text" id="ctrl_${sectionId}_alt_en" class="etb-image-control etb-image-alt etb-lang-en" value="${enImgDetails.alt}" data-target-attr="alt" data-lang="en" />
                            </div>`;
                 if (isTranslatable) {
                    controlHtml += `<div class="etb-control-group">
                                <label for="ctrl_${sectionId}_alt_pt">Alt Text (PT):</label>
                                <input type="text" id="ctrl_${sectionId}_alt_pt" class="etb-image-control etb-image-alt etb-lang-pt" value="${ptAlt}" data-target-attr="alt" data-lang="pt" />
                            </div>
                            <div class="etb-control-group">
                                <label for="ctrl_${sectionId}_alt_es">Alt Text (ES):</label>
                                <input type="text" id="ctrl_${sectionId}_alt_es" class="etb-image-control etb-image-alt etb-lang-es" value="${esAlt}" data-target-attr="alt" data-lang="es" />
                            </div>`;
                 }
            }
            // Add more control types (checkbox, toggle) as needed

            controlHtml += `</div>`; // Close etb-sortable-section
            controlsContainer.append(controlHtml);
        });

        if (controlsContainer.children().length === 0) {
            controlsContainer.html('<p class="etb-no-controls-message">No editable sections (with data-etb-section-id) found in the template.</p>');
        }

        initializeSortable();
    }

    function initializeSortable() {
        const controlsContainer = $('#etb-sortable-items-container');
        if (controlsContainer.data('ui-sortable')) { // Destroy if already initialized
            controlsContainer.sortable("destroy");
        }
        controlsContainer.sortable({
            placeholder: "etb-sortable-placeholder",
            axis: "y",
            handle: "h5",
            update: function(event, ui) {
                setDirty(true);
                regenerateAllPreviewsFromControls(); // Update preview based on new order
                console.log("Section order updated.");
            }
        }).disableSelection();
    }

    function getSectionDataFromControls(sectionId, lang) {
        const sectionDiv = $(`.etb-sortable-section[data-etb-section-id="${sectionId}"]`);
        const type = sectionDiv.data('etb-section-type');
        let data = {};

        if (type === 'text' || type === 'textarea') {
            data.text = $(`#ctrl_${sectionId}_${lang}`).val();
        } else if (type === 'image') {
            // For images, EN holds the src, other languages might have different alt texts
            data.src = $(`#ctrl_${sectionId}_src`).val(); // Assuming src is not translated per se
            data.alt = $(`#ctrl_${sectionId}_alt_${lang}`).val();
            if (lang !== 'en' && !data.alt) { // Fallback to EN alt if translated is empty
                data.alt = $(`#ctrl_${sectionId}_alt_en`).val();
            }
        }
        return data;
    }

    // Regenerate a single language preview based on current controls and their order
    function regenerateLangPreview(lang) {
        let newHtml = baseTemplateHtml; // Start with the base structure
        let tempDom = $('<div>').html(newHtml); // Create a mutable DOM copy

        // Iterate over sections in the sidebar order
        $('#etb-sortable-items-container .etb-sortable-section').each(function() {
            const sectionId = $(this).data('etb-section-id');
            const sectionData = getSectionDataFromControls(sectionId, lang);
            const targetElementInDom = tempDom.find(`[data-etb-section-id="${sectionId}"]`);

            if (targetElementInDom.length) {
                const type = $(this).data('etb-section-type');
                if (type === 'text' || type === 'textarea') {
                    // This needs to be smarter, e.g., update specific child element if section is a wrapper
                    targetElementInDom.text(sectionData.text);
                } else if (type === 'image') {
                    targetElementInDom.find('img').attr('src', sectionData.src).attr('alt', sectionData.alt);
                }
            }
        });
        updatePreview(lang, tempDom.html());
    }

    function regenerateAllPreviewsFromControls() {
        regenerateLangPreview('en');
        regenerateLangPreview('pt');
        regenerateLangPreview('es');
    }


    // --- Event Listeners ---
    // Delegated event listener for content controls (text, textarea)
    $(document).on('input change', '.etb-content-control', function() {
        const $input = $(this);
        const lang = $input.data('lang');
        const sectionId = $input.closest('.etb-sortable-section').data('etb-section-id');
        const isTranslatable = $input.closest('.etb-sortable-section').data('etb-translatable') === true;

        if (lang === 'en' && isTranslatable) {
            const enText = $input.val();
            // Update corresponding PT and ES fields if they haven't been manually edited (or if we want to always force re-translate)
            // To check for manual edit, we could add a data attribute like 'data-manually-edited'
            const $ptInput = $(`#ctrl_${sectionId}_pt`);
            const $esInput = $(`#ctrl_${sectionId}_es`);

            if ($ptInput.length && !$ptInput.data('manually-edited')) {
                $ptInput.val(translate(enText, 'pt', sectionId));
            }
            if ($esInput.length && !$esInput.data('manually-edited')) {
                $esInput.val(translate(enText, 'es', sectionId));
            }
            regenerateAllPreviewsFromControls(); // If EN changes, all might need re-translation
        } else {
            // If PT or ES field is edited, mark it as manually edited
            if (lang !== 'en') {
                $input.data('manually-edited', true);
            }
            regenerateLangPreview(lang); // Regenerate only the affected language preview
        }
        setDirty(true);
    });

    // Delegated event listener for image controls
    $(document).on('input change', '.etb-image-control', function() {
        const $input = $(this);
        const lang = $input.data('lang'); // Relevant for alt tags
        const sectionId = $input.closest('.etb-sortable-section').data('etb-section-id');
        const isTranslatable = $input.closest('.etb-sortable-section').data('etb-translatable') === true;
        const isSrcField = $input.hasClass('etb-image-src');

        if (isSrcField) { // Image source changed (usually only one src input for all langs)
            regenerateAllPreviewsFromControls();
        } else if (lang === 'en' && isTranslatable) { // EN alt text changed
            const enAltText = $input.val();
            const $ptInput = $(`#ctrl_${sectionId}_alt_pt`);
            const $esInput = $(`#ctrl_${sectionId}_alt_es`);

            if ($ptInput.length && !$ptInput.data('manually-edited')) {
                $ptInput.val(translate(enAltText, 'pt', sectionId + '_alt'));
            }
            if ($esInput.length && !$esInput.data('manually-edited')) {
                $esInput.val(translate(enAltText, 'es', sectionId + '_alt'));
            }
            regenerateAllPreviewsFromControls();
        } else { // PT or ES alt text changed
             if (lang !== 'en') {
                $input.data('manually-edited', true);
            }
            regenerateLangPreview(lang);
        }
        setDirty(true);
    });


    // --- Helper: Show Status Message ---
    function showStatusMessage(message, isError = false) {
        // Simple alert for now, can be replaced with a dedicated message area in UI
        alert(message);
        if (isError) console.error(message); else console.log(message);
    }

    // --- Template List Management ---
    function loadTemplatesList() {
        $.ajax({
            url: etb_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'etb_load_templates_list',
                nonce: etb_ajax_obj.nonce
            },
            success: function(response) {
                if (response.success) {
                    const select = $('#etb-template-select');
                    select.empty().append($('<option>', { value: '', text: etb_ajax_obj.text_strings.select_template_to_load || '-- Select a Template --' }));
                    response.data.forEach(function(template) {
                        select.append($('<option>', {
                            value: template.id,
                            text: template.name
                        }));
                    });
                } else {
                    showStatusMessage(response.data.message || 'Error loading templates list.', true);
                }
            },
            error: function(xhr) {
                showStatusMessage('AJAX error loading templates list: ' + xhr.statusText, true);
            }
        });
    }

    // --- Load Template Data ---
    function loadTemplateData(templateId) {
        if (!templateId) {
            showStatusMessage(etb_ajax_obj.text_strings.select_template_to_load || 'Please select a template.', true);
            return;
        }
        $.ajax({
            url: etb_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'etb_load_template_data',
                nonce: etb_ajax_obj.nonce,
                template_id: templateId
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    currentTemplateId = data.id;
                    $('#etb-current-template-name').val(data.name).show();

                    // Load EN content to parse for controls and set as base structure for this load
                    // The other languages content will be used to populate controls if different,
                    // or regenerated if structure changed.
                    baseTemplateHtml = data.content_en; // This becomes the reference for current structure
                    currentPreviews.en = data.content_en;
                    currentPreviews.pt = data.content_pt;
                    currentPreviews.es = data.content_es;

                    updatePreview('en', currentPreviews.en);
                    updatePreview('pt', currentPreviews.pt);
                    updatePreview('es', currentPreviews.es);

                    parseTemplateForControls(currentPreviews.en); // Parse current EN structure for controls

                    // After parsing, repopulate controls with loaded language data
                    // This assumes parseTemplateForControls sets up control IDs like ctrl_{sectionId}_{lang}
                    $('#etb-sortable-items-container .etb-sortable-section').each(function() {
                        const sectionId = $(this).data('etb-section-id');
                        const sectionType = $(this).data('etb-section-type');

                        if (sectionType === 'text' || sectionType === 'textarea') {
                            // Find the EN text from the loaded EN HTML to ensure correct base for translation
                            let tempDomEn = $('<div>').html(data.content_en);
                            let enTextForSection = tempDomEn.find(`[data-etb-section-id="${sectionId}"]`).text().trim();

                            $(`#ctrl_${sectionId}_en`).val(enTextForSection);
                            $(`#ctrl_${sectionId}_pt`).val(extractSectionText(data.content_pt, sectionId) || translate(enTextForSection, 'pt'));
                            $(`#ctrl_${sectionId}_es`).val(extractSectionText(data.content_es, sectionId) || translate(enTextForSection, 'es'));
                        } else if (sectionType === 'image') {
                             let tempDomEn = $('<div>').html(data.content_en);
                             let imgEn = tempDomEn.find(`[data-etb-section-id="${sectionId}"] img`);
                             $(`#ctrl_${sectionId}_src`).val(imgEn.attr('src'));
                             $(`#ctrl_${sectionId}_alt_en`).val(imgEn.attr('alt'));

                             let tempDomPt = $('<div>').html(data.content_pt);
                             let imgPt = tempDomPt.find(`[data-etb-section-id="${sectionId}"] img`);
                             $(`#ctrl_${sectionId}_alt_pt`).val(imgPt.attr('alt') || translate(imgEn.attr('alt') || '', 'pt'));

                             let tempDomEs = $('<div>').html(data.content_es);
                             let imgEs = tempDomEs.find(`[data-etb-section-id="${sectionId}"] img`);
                             $(`#ctrl_${sectionId}_alt_es`).val(imgEs.attr('alt') || translate(imgEn.attr('alt') || '', 'es'));
                        }
                    });


                    setDirty(false);
                    $('#etb-save-template-button').text(etb_ajax_obj.text_strings.save_template || 'Save Template').prop('disabled', true);
                    $('#etb-clone-template-button, #etb-delete-template-button, #etb-export-html-button, #etb-reset-template-button').prop('disabled', false);
                    showStatusMessage(etb_ajax_obj.text_strings.template_loaded || 'Template loaded.');
                } else {
                    showStatusMessage(response.data.message || 'Error loading template data.', true);
                }
            },
            error: function(xhr) {
                showStatusMessage('AJAX error loading template data: ' + xhr.statusText, true);
            }
        });
    }
    // Helper to extract text for a section from a full HTML string
    function extractSectionText(htmlString, sectionId) {
        if (!htmlString) return '';
        let tempDom = $('<div>').html(htmlString);
        return tempDom.find(`[data-etb-section-id="${sectionId}"]`).text().trim();
    }


    // --- Save Template Data ---
    function saveTemplateData() {
        const templateName = $('#etb-current-template-name').val().trim();
        if (!templateName) {
            showStatusMessage(etb_ajax_obj.text_strings.enter_template_name || 'Please enter a template name.', true);
            $('#etb-current-template-name').focus();
            return;
        }

        // Regenerate all previews to ensure currentPreviews are up-to-date before saving
        regenerateAllPreviewsFromControls();

        const sectionsOrder = [];
        $('#etb-sortable-items-container .etb-sortable-section').each(function() {
            sectionsOrder.push($(this).data('etb-section-id'));
        });

        const dataToSave = {
            action: 'etb_save_template',
            nonce: etb_ajax_obj.nonce,
            name: templateName,
            content_en: currentPreviews.en,
            content_pt: currentPreviews.pt,
            content_es: currentPreviews.es,
            sections_order: JSON.stringify(sectionsOrder),
            settings: JSON.stringify({}) // Placeholder for future settings
        };

        if (currentTemplateId) {
            dataToSave.template_id = currentTemplateId;
        }

        $.ajax({
            url: etb_ajax_obj.ajax_url,
            type: 'POST',
            data: dataToSave,
            success: function(response) {
                if (response.success) {
                    currentTemplateId = response.data.template_id; // Update ID if new template
                    $('#etb-current-template-name').val(response.data.name); // Update name in case it was sanitized
                    setDirty(false);
                    $('#etb-save-template-button').text(etb_ajax_obj.text_strings.save_template || 'Save Template');
                    showStatusMessage(response.data.message || etb_ajax_obj.text_strings.template_saved);
                    loadTemplatesList(); // Refresh dropdown
                    // Re-select the saved template in dropdown
                    $('#etb-template-select').val(currentTemplateId);
                } else {
                    showStatusMessage(response.data.message || etb_ajax_obj.text_strings.error_saving, true);
                }
            },
            error: function(xhr) {
                showStatusMessage('AJAX error saving template: ' + xhr.statusText, true);
            }
        });
    }

    // --- Delete Template ---
    function deleteTemplate() {
        const templateIdToDelete = $('#etb-template-select').val();
        if (!templateIdToDelete) {
            showStatusMessage(etb_ajax_obj.text_strings.select_template_to_delete || 'Please select a template to delete.', true);
            return;
        }

        if (!confirm(etb_ajax_obj.text_strings.confirm_delete || 'Are you sure you want to delete this template?')) {
            return;
        }

        $.ajax({
            url: etb_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'etb_delete_template',
                nonce: etb_ajax_obj.nonce,
                template_id: templateIdToDelete
            },
            success: function(response) {
                if (response.success) {
                    showStatusMessage(response.data.message || etb_ajax_obj.text_strings.template_deleted);
                    loadTemplatesList(); // Refresh list
                    // Reset UI if the deleted template was the one being edited
                    if (currentTemplateId == templateIdToDelete) {
                        currentTemplateId = null;
                        $('#etb-current-template-name').val('').hide();
                        $('#etb-sortable-items-container').empty().html('<p class="etb-no-controls-message">Select or create a template.</p>');
                        updatePreview('en', ''); updatePreview('pt', ''); updatePreview('es', '');
                        setDirty(false);
                        $('#etb-save-template-button, #etb-clone-template-button, #etb-delete-template-button, #etb-export-html-button, #etb-reset-template-button').prop('disabled', true);
                    }
                } else {
                    showStatusMessage(response.data.message || 'Error deleting template.', true);
                }
            },
            error: function(xhr) {
                showStatusMessage('AJAX error deleting template: ' + xhr.statusText, true);
            }
        });
    }

    // --- Clone Template ---
    function cloneTemplate() {
        const templateIdToClone = $('#etb-template-select').val();
        if (!templateIdToClone) {
            showStatusMessage(etb_ajax_obj.text_strings.select_template_to_clone || 'Please select a template to clone.', true);
            return;
        }

        $.ajax({
            url: etb_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'etb_clone_template',
                nonce: etb_ajax_obj.nonce,
                template_id: templateIdToClone
            },
            success: function(response) {
                if (response.success) {
                    showStatusMessage(response.data.message || etb_ajax_obj.text_strings.template_cloned);
                    loadTemplatesList(); // Refresh list
                    // Automatically load the new cloned template for editing
                    const newTemplate = response.data.new_template;
                    $('#etb-template-select').val(newTemplate.id); // Select it in dropdown
                    loadTemplateData(newTemplate.id); // Load its data
                } else {
                    showStatusMessage(response.data.message || 'Error cloning template.', true);
                }
            },
            error: function(xhr) {
                showStatusMessage('AJAX error cloning template: ' + xhr.statusText, true);
            }
        });
    }

    // --- Export HTML ---
    function exportHtml() {
        if (!currentTemplateId && !isDirty) { // Also check isDirty for new unsaved templates
            showStatusMessage(etb_ajax_obj.text_strings.no_template_to_export || 'Load or create a template to export.', true);
            return;
        }

        // For simplicity, export English version. Could be enhanced with a language choice.
        // Ensure currentPreviews.en is up-to-date if there are pending changes.
        regenerateLangPreview('en'); // Ensure EN preview is current based on controls
        const htmlContent = currentPreviews.en;
        const templateName = $('#etb-current-template-name').val().trim() || 'email-template';
        const filename = templateName.replace(/[^a-z0-9]/gi, '_').toLowerCase() + '_en.html';

        const blob = new Blob([htmlContent], { type: 'text/html' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(link.href); // Clean up
        showStatusMessage(etb_ajax_obj.text_strings.template_exported || 'Template HTML exported.');
    }

    // --- Reset Changes ---
    function resetChanges() {
        if (!isDirty) {
            showStatusMessage(etb_ajax_obj.text_strings.no_changes_to_reset || 'No unsaved changes to reset.');
            return;
        }
        if (!confirm(etb_ajax_obj.text_strings.confirm_reset_changes || 'Are you sure you want to discard unsaved changes?')) {
            return;
        }

        if (currentTemplateId) {
            // If it's an existing template, reload its saved state
            loadTemplateData(currentTemplateId);
        } else {
            // If it's a new, unsaved template, reset to the initial base structure
            loadBaseStructure(etb_ajax_obj.initial_template_html);
            $('#etb-current-template-name').val(''); // Clear name for new template
        }
        setDirty(false); // Changes have been discarded or reloaded
        showStatusMessage(etb_ajax_obj.text_strings.changes_reset || 'Changes have been reset.');
    }


    // --- Document Ready ---
    $(function() {
        console.log('Email Template Builder JS Initialized.');
        console.log('AJAX Object:', etb_ajax_obj);

        translations = etb_ajax_obj.translations || { // Use translations from PHP if provided
            "Hello {{name}}!": { "PT": "Olá {{name}}!", "ES": "¡Hola {{name}}!" },
            "This is the default body content.": { "PT": "Este é o conteúdo padrão do corpo.", "ES": "Este es el contenido del cuerpo predeterminado." },
        };

        if ($('#etb-preview-tabs').length) {
            $('#etb-preview-tabs').tabs();
        }

        loadTemplatesList(); // Load templates into dropdown on page load

        // --- Event Handlers for CRUD ---
        $('#etb-create-new-template-button').on('click', function() {
            currentTemplateId = null;
            $('#etb-current-template-name').val('').show().focus();
            $('#etb-save-template-button').prop('disabled', false).text(etb_ajax_obj.text_strings.save_template || 'Save New Template');
            $('#etb-template-select').val('');

            loadBaseStructure(etb_ajax_obj.initial_template_html);

            // Button states for new template
            $('#etb-load-template-button').prop('disabled', true); // No template selected to load
            $('#etb-clone-template-button').prop('disabled', true);
            $('#etb-delete-template-button').prop('disabled', true);
            $('#etb-export-html-button').prop('disabled', true); // Can't export unsaved
            $('#etb-reset-template-button').prop('disabled', true); // Nothing to reset to
            $('#etb-save-template-button').prop('disabled', false); // Can save the new template

            console.log("Creating new template from base structure.");
        });

        $('#etb-load-template-button').on('click', function() {
            const selectedId = $('#etb-template-select').val();
            if (selectedId) {
                loadTemplateData(selectedId);
            } else {
                showStatusMessage(etb_ajax_obj.text_strings.select_template_to_load || 'Please select a template to load.', true);
            }
        });

        $('#etb-save-template-button').on('click', function() {
            saveTemplateData();
        });

        $('#etb-delete-template-button').on('click', function() {
            deleteTemplate();
        });

        $('#etb-clone-template-button').on('click', function() {
            cloneTemplate();
        });

        $('#etb-export-html-button').on('click', function() {
            exportHtml();
        });

        $('#etb-reset-template-button').on('click', function() {
            resetChanges();
        });

        // Enable/disable load, clone, delete buttons based on selection
        $('#etb-template-select').on('change', function() {
            const selectedId = $(this).val();
            if (selectedId) {
                $('#etb-load-template-button').prop('disabled', false);
                $('#etb-clone-template-button').prop('disabled', false);
                $('#etb-delete-template-button').prop('disabled', false);
            } else {
                $('#etb-load-template-button').prop('disabled', true);
                $('#etb-clone-template-button').prop('disabled', true);
                $('#etb-delete-template-button').prop('disabled', true);
            }
        });

        // Initial button states
        // Create is always enabled. Others depend on context.
        $('#etb-load-template-button, #etb-clone-template-button, #etb-delete-template-button, #etb-save-template-button, #etb-export-html-button, #etb-reset-template-button').prop('disabled', true);
        $('#etb-create-new-template-button').prop('disabled', false);


    }); // End document ready

})(jQuery);
