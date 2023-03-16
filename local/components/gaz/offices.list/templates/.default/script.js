class YaMaps {

    rootElement = null
    center = []
    zoom = null
    dataObj = {}
    mapInctance = null
    objectManager = null
    mapId = null
    placemarks = {}

    constructor(dataObj = null) {
        if (!dataObj) {
            return
        }
        this.dataObj = dataObj
    }

    init() {
        this.mapId = this.dataObj.MAP_ID
        const mapElement = document.querySelector(`[data-mapid='${this.mapId}']`)
        if (!mapElement) {
            return
        }
        this.center = this.dataObj.CENTER.split(",")
        this.zoom = this.dataObj.ZOOM || 12
        this.rootElement = mapElement
        this.marker = this.dataObj.MARKER

        this.initYaMap()

    }
    openballon({target}){
        const root = target.closest('.js_ballon__open')
        const placemarkId = root.dataset.placemarkid
        const balloonId = this.placemarks[placemarkId].id
        this.objectManager.objects.balloon.open(balloonId)
    }

    addListeners(){
        const rootDiv = this.rootElement.closest(`.js_map__${this.mapId}`)
        const dataset = rootDiv.dataset

        rootDiv.querySelectorAll(dataset.openhandler).forEach(el => {
            el.addEventListener('click', (evn) => {
                this.openballon.call(this, evn)
            })
        })

    }

    initYaMap() {

        ymaps.ready(() => {
            this.mapInctance = new ymaps.Map(this.rootElement, {
                center: this.center,
                zoom: this.zoom,
            }, {
                suppressMapOpenBlock: true
            });

            this.objectManager = new ymaps.ObjectManager({
                clusterize: false,
                clusterDisableClickZoom: true,
            });
            this.mapInctance.geoObjects.add(this.objectManager);

            this.addCustomBalloonTpl()

            this.addObjects()

            this.rootElement.classList.add('initialized')

            this.addListeners()
        })
    }


    addCustomBalloonTpl() {

        const MyBalloonLayout = ymaps.templateLayoutFactory.createClass(
            '<div class="map-balloon">' +
            '<a class="close"><img class="map-balloon__close" src="/local/components/gaz/offices.list/templates/.default/images/clear.png"></a>' +
            '<div class="map-balloon__inner">' +
            '$[[options.contentLayout observeSize]]' +
            '</div>' +
            '<div class="map-balloon__arrow"></div>' +
            '</div>',
            {
                build: function () {
                    this.constructor.superclass.build.call(this);
                    this._$element = $('.map-balloon', this.getParentElement());
                    this.applyElementOffset();
                    this._$element
                        .find('.close')
                        .on('click', $.proxy(this.onCloseClick, this));
                },
                clear: function () {
                    this._$element.find('.close').off('click');
                    this.constructor.superclass.clear.call(this);
                },
                onSublayoutSizeChange: function () {
                    MyBalloonLayout.superclass.onSublayoutSizeChange.apply(
                        this,
                        arguments
                    );
                    if (!this._isElement(this._$element)) {
                        return;
                    }
                    this.applyElementOffset();
                    this.events.fire('shapechange');
                },
                applyElementOffset: function () {
                    this._$element.css({
                        left: -(this._$element[0].offsetWidth / 2),
                        top: -(
                            this._$element[0].offsetHeight +
                            this._$element.find('.map-balloon__arrow')[0].offsetHeight
                        ),
                    });
                },
                onCloseClick: function (e) {
                    e.preventDefault();
                    this.events.fire('userclose');
                },
                getShape: function () {
                    if (!this._isElement(this._$element)) {
                        return MyBalloonLayout.superclass.getShape.call(this);
                    }
                    var position = this._$element.position();
                    return new ymaps.shape.Rectangle(
                        new ymaps.geometry.pixel.Rectangle([
                            [position.left, position.top],
                            [
                                position.left + this._$element[0].offsetWidth,
                                position.top +
                                this._$element[0].offsetHeight +
                                this._$element.find('.map-balloon__arrow')[0]
                                    .offsetHeight,
                            ],
                        ])
                    );
                },
                _isElement: function (element) {
                    return (
                        element && element[0] && element.find('.map-balloon__arrow')[0]
                    );
                },
            }
        );
        const MyBalloonContentLayout = ymaps.templateLayoutFactory.createClass(
            '<div class="map-balloon__content">$[properties.balloonContent]</div>'
        );
        this.objectManager.objects.options.set({
            balloonContentLayout: MyBalloonContentLayout,
            balloonLayout: MyBalloonLayout,
            balloonMinHeight: 80
        });
    }


    addObjects() {

        for (const item of this.dataObj.ITEMS) {

            const key = `${this.mapId}_${item.ID}`

            this.placemarks[key] = {
                type: "Feature",
                id: item.ID,
                geometry: {
                    type: "Point",
                    coordinates: item.COORDS.split(","),
                },
                options: {
                    id: item.ID,
                    name: item.NAME,
                    iconLayout: "default#image",
                    iconImageHref: this.marker,
                    iconImageSize: [28, 38],
                    iconImageOffset: [-14, -38],
                },
                properties: {
                    balloonHeader: item.NAME,
                    balloonContent: item.BALLOON,
                    balloonShadow: false,
                    balloonPanelMaxMapArea: 0,
                },
            };
            this.objectManager.add( this.placemarks[key])
        }
    }

}