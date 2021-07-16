document.querySelector('.sitemap-button').onclick = (e) => {

    e.preventDefault()

    createSitemap();

}

    let linksCounter = 0;

    function createSitemap() {

        linksCounter++;


        Ajax({data: {ajax:'sitemap', linksCounter:linksCounter}})
        .then((res) => {
            console.log('успех - ' + res);


        })
        .catch((res) => {

            console.log('ошибка - ' + res);
            //Вызывая  createSitemap() из блока .catch(()  - мы таки получаем полную карту сайта.
            // Не смотря на две fatal error которые выбрасывались системой
            createSitemap();

        });
}