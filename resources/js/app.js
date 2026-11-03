import Alpine from '@alpinejs/csp';

// The CSP build of Alpine only runs components registered here, never
// expressions written inline in HTML, so the page needs no unsafe-eval.

Alpine.data('fixedCount', () => ({
    fixed: null,
    total: null,
    init() {
        this.fixed = Number(this.$el.dataset.fixed);
        this.total = Number(this.$el.dataset.total);
        const url = this.$el.dataset.url;
        this.timer = setInterval(() => this.refresh(url), 20000);
    },
    destroy() {
        clearInterval(this.timer);
    },
    async refresh(endpoint) {
        try {
            const response = await fetch(endpoint, { headers: { Accept: 'application/json' } });
            if (!response.ok) {
                return;
            }
            const data = await response.json();
            this.fixed = data.fixed;
            this.total = data.total;
            this.$refs.fixed.textContent = data.fixed;
            this.$refs.total.textContent = data.total;
        } catch {
            // A dropped connection on a slow network is normal. The next tick tries again.
        }
    },
}));

Alpine.data('photoPicker', () => ({
    name: '',
    pick(event) {
        const file = event.target.files[0];
        this.name = file ? file.name : '';
    },
}));

Alpine.data('charCount', () => ({
    count: 0,
    update(event) {
        this.count = event.target.value.length;
    },
}));

Alpine.data('statusForm', () => ({
    status: '',
    init() {
        const checked = this.$el.querySelector('input[name="status"]:checked');
        this.status = checked ? checked.value : '';
    },
    choose(event) {
        this.status = event.target.value;
    },
    isDone() {
        return this.status === 'done';
    },
}));

window.Alpine = Alpine;
Alpine.start();
