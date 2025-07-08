// This is a temporary file to hold utility functions that will be merged into email-builder.js

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
        };
    } else { // text, textarea
        // This needs to be specific to how content is structured within your section.
        // For example, if it's just the text of the section div itself:
        return sectionEl.text().trim();
        // Or if it's a specific child: sectionEl.find('.content-element').text().trim();
    }
}

// Placeholder for translate function to avoid errors if not defined in main yet
function translate(text, targetLang, sectionId = null) {
    const translations = (typeof window.etb_translations !== 'undefined') ? window.etb_translations : {}; // Access global if set
    if (sectionId && translations[sectionId] && translations[sectionId][text] && translations[sectionId][text][targetLang.toUpperCase()]) {
        return translations[sectionId][text][targetLang.toUpperCase()];
    }
    if (translations[text] && translations[text][targetLang.toUpperCase()]) {
        return translations[text][targetLang.toUpperCase()];
    }
    if (text.match(/\{\{[^{}]+\}\}/g)) { return text; }
    if (targetLang.toLowerCase() === 'en') { return text; }
    return `[${targetLang.toUpperCase()}] ${text}`;
}
