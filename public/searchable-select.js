/**
 * Componente de Select Searchable (Combobox con búsqueda)
 * Reemplaza los select normales con un input searchable que filtra opciones en tiempo real
 */

class SearchableSelect {
    constructor(selectElement) {
        this.selectEl = selectElement;
        this.originalSelect = selectElement.cloneNode(true);
        this.selectedValue = selectElement.value;
        this.selectedText = '';
        this.isOpen = false;
        this.filteredOptions = [];
        this.allOptions = [];
        this.init();
    }

    init() {
        // Obtener todas las opciones
        this.allOptions = Array.from(this.selectEl.options).map(opt => ({
            value: opt.value,
            text: opt.textContent,
            selected: opt.selected
        }));

        // Obtener texto seleccionado inicial
        const selectedOpt = this.selectEl.options[this.selectEl.selectedIndex];
        this.selectedText = selectedOpt ? selectedOpt.textContent : '';

        // Crear el contenedor wrapper
        const wrapper = document.createElement('div');
        wrapper.className = 'searchable-select-wrapper';
        wrapper.style.cssText = `
            position: relative;
            width: 100%;
        `;

        // Crear input de búsqueda
        const input = document.createElement('input');
        input.type = 'text';
        input.className = 'searchable-select-input';
        input.placeholder = 'Buscar...';
        input.value = this.selectedText;
        input.style.cssText = `
            width: 100%;
            padding: 10px;
            border-radius: 8px;
            border: 0.5px solid #D0CCC6;
            background: #fff;
            font-size: 14px;
            font-family: inherit;
            cursor: pointer;
        `;

        // Crear lista de opciones
        const optionsContainer = document.createElement('div');
        optionsContainer.className = 'searchable-select-options';
        optionsContainer.style.cssText = `
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 0.5px solid #D0CCC6;
            border-top: none;
            border-radius: 0 0 8px 8px;
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        `;

        // Crear opciones
        this.renderOptions(this.allOptions, optionsContainer);

        wrapper.appendChild(input);
        wrapper.appendChild(optionsContainer);

        // Reemplazar el select original
        this.selectEl.style.display = 'none';
        this.selectEl.parentNode.insertBefore(wrapper, this.selectEl);

        // Event listeners
        input.addEventListener('focus', () => this.open(optionsContainer));
        input.addEventListener('blur', () => setTimeout(() => this.close(optionsContainer, input), 200));
        input.addEventListener('input', (e) => this.search(e.target.value, optionsContainer));

        this.input = input;
        this.optionsContainer = optionsContainer;
    }

    renderOptions(options, container) {
        container.innerHTML = '';

        if (options.length === 0) {
            const noResults = document.createElement('div');
            noResults.style.cssText = `
                padding: 10px;
                text-align: center;
                color: #999;
                font-size: 12px;
            `;
            noResults.textContent = 'Sin resultados';
            container.appendChild(noResults);
            return;
        }

        options.forEach(opt => {
            const optionDiv = document.createElement('div');
            optionDiv.className = 'searchable-select-option';
            optionDiv.style.cssText = `
                padding: 10px;
                cursor: pointer;
                border-bottom: 0.5px solid #f0f0f0;
                transition: background 0.2s;
            `;
            optionDiv.textContent = opt.text;

            if (opt.value === this.selectedValue) {
                optionDiv.style.background = '#E3F2FD';
                optionDiv.style.color = '#1F5C8B';
                optionDiv.style.fontWeight = 'bold';
            }

            optionDiv.addEventListener('mouseenter', () => {
                optionDiv.style.background = '#f5f5f5';
            });

            optionDiv.addEventListener('mouseleave', () => {
                if (opt.value !== this.selectedValue) {
                    optionDiv.style.background = 'white';
                }
            });

            optionDiv.addEventListener('click', () => {
                this.select(opt, optionDiv);
            });

            container.appendChild(optionDiv);
        });
    }

    search(query, container) {
        const lowerQuery = query.toLowerCase();
        this.filteredOptions = this.allOptions.filter(opt =>
            opt.text.toLowerCase().includes(lowerQuery)
        );
        this.renderOptions(this.filteredOptions, container);
    }

    select(option, optionDiv) {
        this.selectedValue = option.value;
        this.selectedText = option.text;
        this.input.value = option.text;

        // Actualizar el select original
        this.selectEl.value = option.value;
        this.selectEl.dispatchEvent(new Event('change', { bubbles: true }));

        this.close(this.optionsContainer, this.input);
    }

    open(container) {
        container.style.display = 'block';
        this.input.style.borderRadius = '8px 8px 0 0';
        this.isOpen = true;
    }

    close(container, input) {
        container.style.display = 'none';
        input.style.borderRadius = '8px';
        this.isOpen = false;
    }
}

// Inicializar automáticamente selects con la clase 'searchable-select'
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('select.searchable-select').forEach(select => {
        new SearchableSelect(select);
    });
});

// También exportar para uso manual
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SearchableSelect;
}
