/**
 * Generates initials from a full name.
 * - Single name: Returns the first two letters.
 * - Multiple names: Returns the first letter of the first and last name.
 * @param {string} name The full name.
 * @returns {string} The generated initials in uppercase.
 */
export function help_generate_initials(name) {
    name = name ? name.trim() : '';
    if (!name) {
        return '';
    }

    // Split name and remove empty parts
    const nameParts = name.split(' ').filter(part => part.trim() !== '');
    const nameCount = nameParts.length;

    if (nameCount === 0) {
        return '';
    }

    if (nameCount === 1) {
        // Single name: take first 2 characters
        return nameParts[0].substring(0, 2).toUpperCase();
    }

    // Multiple names: take first letter of first and last name
    const firstInitial = nameParts[0].substring(0, 1);
    const lastInitial = nameParts[nameCount - 1].substring(0, 1);

    return (firstInitial + lastInitial).toUpperCase();
}

/**
 * Selects a consistent color palette based on string initials.
 * Uses a simple hashing algorithm to ensure the same initials always get the same color.
 * @param {string} initials The initials to generate a color for.
 * @returns {{background: string, text: string}} An object with TailwindCSS color classes.
 */
export function help_get_avatar_colors(initials) {
    const colorPalette = [
        { background: 'bg-blue-100', text: 'text-blue-800' },
        { background: 'bg-green-100', text: 'text-green-800' },
        { background: 'bg-orange-100', text: 'text-orange-800' },
        { background: 'bg-purple-100', text: 'text-purple-800' },
        { background: 'bg-red-100', text: 'text-red-800' },
        { background: 'bg-teal-100', text: 'text-teal-800' },
        { background: 'bg-yellow-100', text: 'text-yellow-800' },
        { background: 'bg-pink-100', text: 'text-pink-800' },
        { background: 'bg-indigo-100', text: 'text-indigo-800' },
        { background: 'bg-lime-100', text: 'text-lime-800' }
    ];

    if (!initials) {
        return colorPalette[0]; // Return a default color if no initials
    }

    // A simple, effective hashing function to get a consistent index from a string.
    // This serves the same purpose as crc32 in your PHP.
    let hash = 0;
    for (let i = 0; i < initials.length; i++) {
        const char = initials.charCodeAt(i);
        hash = ((hash << 5) - hash) + char;
        hash = hash & hash; // Convert to 32bit integer
    }

    const index = Math.abs(hash) % colorPalette.length;

    return colorPalette[index];
}