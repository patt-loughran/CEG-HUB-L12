import './bootstrap';

// Import your new helper functions
import { help_generate_initials, help_get_avatar_colors } from './helpers.js';

// Make the functions globally accessible by attaching them to the window object
window.help_generate_initials = help_generate_initials;
window.help_get_avatar_colors = help_get_avatar_colors;

import Alpine from 'alpinejs'

window.Alpine = Alpine
Alpine.start()