// Глобальные переменные
let currentPage = 1;
let currentFilters = {};
const eventsPerPage = 20;

// Загрузка мероприятий при старте
document.addEventListener('DOMContentLoaded', function() {
    loadEvents();
    setupEventListeners();
});

function setupEventListeners() {
    // Поиск по кнопке
    document.getElementById('searchButton').addEventListener('click', function() {
        const query = document.getElementById('searchInput').value;
        if (query.trim()) {
            currentFilters.search = query;
            currentPage = 1;
            loadEvents();
        }
    });

    // Поиск по нажатию Enter
    document.getElementById('searchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            document.getElementById('searchButton').click();
        }
    });

    // Применение фильтров
    document.getElementById('applyFilters').addEventListener('click', function() {
        currentFilters = {
            city: document.getElementById('cityFilter').value,
            age: document.getElementById('ageFilter').value,
            category: document.getElementById('categoryFilter').value,
            price: document.getElementById('priceFilter').value
        };
        currentPage = 1;
        loadEvents();
    });

    // Сброс фильтров
    document.getElementById('resetFilters').addEventListener('click', function() {
        document.getElementById('cityFilter').value = '';
        document.getElementById('ageFilter').value = '';
        document.getElementById('categoryFilter').value = '';
        document.getElementById('priceFilter').value = '';
        document.getElementById('searchInput').value = '';
        currentFilters = {};
        currentPage = 1;
        loadEvents();
    });
}

function loadEvents() {
    const container = document.getElementById('eventsList');
    container.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary-custom" role="status">
                <span class="visually-hidden">Загрузка...</span>
            </div>
            <p class="mt-3">Ищем мероприятия...</p>
        </div>`;

    // Формируем параметры запроса
    const params = new URLSearchParams({
        limit: eventsPerPage,
        offset: (currentPage - 1) * eventsPerPage
    });

    // Добавляем фильтры
    if (currentFilters.city) params.append('city', currentFilters.city);
    if (currentFilters.age) params.append('min_age', currentFilters.age);
    if (currentFilters.category) params.append('category', currentFilters.category);
    if (currentFilters.price === 'free') params.append('is_free', '1');
    if (currentFilters.search) params.append('search', currentFilters.search);

    // Запрос к нашему API
    fetch(`api/events.php?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayEvents(data.data);
                updatePagination(data.meta.total);
                updateFilterOptions(data.data);
            } else {
                showError('Ошибка при загрузке мероприятий');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Не удалось загрузить мероприятия');
        });
}

function displayEvents(events) {
    const container = document.getElementById('eventsList');
    
    if (events.length === 0) {
        container.innerHTML = `
            <div class="col-12 text-center py-5">
                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                <h4>Мероприятия не найдены</h4>
                <p class="text-muted">Попробуйте изменить параметры поиска</p>
            </div>`;
        return;
    }

    container.innerHTML = events.map(event => `
        <div class="col-lg-4 col-md-6">
            <div class="event-card">
                <div class="event-image" style="background-image: url('${event.image || 'https://via.placeholder.com/400x200/82B3E1/FFFFFF?text=' + encodeURIComponent(event.category)}')">
                    <span class="event-age">${event.age_category}</span>
                    ${event.is_free ? '<span class="event-free">БЕСПЛАТНО</span>' : ''}
                </div>
                <div class="card-body">
                    <div class="event-category">${event.category || 'Мероприятие'}</div>
                    <h5 class="card-title">${event.title}</h5>
                    <p class="card-text text-muted">${event.description?.substring(0, 100) || 'Описание отсутствует'}...</p>
                    
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="event-city">
                            <i class="fas fa-map-marker-alt me-1"></i>${event.city || 'Город не указан'}
                        </span>
                        <span class="event-date">
                            <i class="fas fa-calendar me-1"></i>${formatDate(event.start_date)}
                        </span>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">
                            <i class="fas fa-users me-1"></i>${event.review_count || 0} отзывов
                        </span>
                        <button class="btn btn-sm btn-primary-custom" onclick="viewEvent(${event.id})">
                            Подробнее <i class="fas fa-arrow-right ms-1"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `).join('');
}

function updatePagination(totalEvents) {
    const pagination = document.getElementById('pagination');
    const totalPages = Math.ceil(totalEvents / eventsPerPage);
    
    if (totalPages <= 1) {
        pagination.innerHTML = '';
        return;
    }

    let pages = '';
    
    // Предыдущая страница
    pages += `
        <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="changePage(${currentPage - 1})">
                <i class="fas fa-chevron-left"></i>
            </a>
        </li>`;
    
    // Номера страниц
    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
            pages += `
                <li class="page-item ${currentPage === i ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="changePage(${i})">${i}</a>
                </li>`;
        } else if (i === currentPage - 3 || i === currentPage + 3) {
            pages += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
    }
    
    // Следующая страница
    pages += `
        <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="changePage(${currentPage + 1})">
                <i class="fas fa-chevron-right"></i>
            </a>
        </li>`;
    
    pagination.innerHTML = pages;
}

function updateFilterOptions(events) {
    // Собираем уникальные города и категории
    const cities = new Set();
    const categories = new Set();
    
    events.forEach(event => {
        if (event.city) cities.add(event.city);
        if (event.category) categories.add(event.category);
    });
    
    // Обновляем селекты (можно сделать более сложную логику)
    console.log('Доступные города:', Array.from(cities));
    console.log('Доступные категории:', Array.from(categories));
}

function changePage(page) {
    currentPage = page;
    loadEvents();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function viewEvent(eventId) {
    // Переход на страницу мероприятия
    window.location.href = `event.php?id=${eventId}`;
}

function formatDate(dateString) {
    if (!dateString) return 'Дата не указана';
    const date = new Date(dateString);
    return date.toLocaleDateString('ru-RU', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
}

function showError(message) {
    const container = document.getElementById('eventsList');
    container.innerHTML = `
        <div class="col-12 text-center py-5">
            <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
            <h4>Ошибка</h4>
            <p class="text-muted">${message}</p>
            <button class="btn btn-primary-custom mt-3" onclick="loadEvents()">
                <i class="fas fa-redo me-2"></i>Попробовать снова
            </button>
        </div>`;
}