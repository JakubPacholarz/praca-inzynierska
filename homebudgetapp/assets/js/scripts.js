document.addEventListener('DOMContentLoaded', () => {
    const calendarCells = document.querySelectorAll('.calendar td');
    const modal = document.getElementById('detailsModal');
    const modalContent = document.getElementById('modalContent');
    const closeModalBtn = modal.querySelector('.btn-close');

    calendarCells.forEach(cell => {
        cell.addEventListener('click', () => {
            const day = cell.dataset.day;
            
           
            const date = new Date();
            const formattedDate = date.toLocaleDateString('en-US', {
                weekday: 'long', 
                month: 'long', 
                day: 'numeric',
                year: 'numeric'
            });

            modalContent.innerHTML = `<p>Details for <strong>${formattedDate}</strong>:</p>`;
            modalContent.innerHTML += `<p>More details will be populated dynamically here...</p>`;
            modal.classList.add('show');
        });
    });

    closeModalBtn.addEventListener('click', () => {
        modal.classList.remove('show');
    });

});
