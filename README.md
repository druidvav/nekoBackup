# nekoBackup 2.0

[nekoBackup](https://github.com/druidvav/nekoBackup) — утилита для создания регулярных бекапов данных на вашем сервере.
На текущий момент поддерживаются следующие виды бекапов:

* директорий и наборов директорий
* базы данных mysql (с помощью mysqldump),
* базы данных postgres (с помощью pg_dump).

Бекапы можно сохранить:

* в файловой системе,
* в хранилище Amazon S3.

## Требования

* php-cli 5.3+
* [composer.phar](http://getcomposer.org/)

## Установка

Поскольку скрипту нужен полный доступ ко всем директориям, указанным в конфиге, желательно запускать его от имени
суперпользователя.

```bash
cd /opt
git clone -b 2.0-stable https://github.com/druidvav/nekoBackup.git nbackup2
composer.phar install
ln -s
```

## Настройка

Настройка осуществляется с помощью yaml-файлов в директории etc.
Перед редактированием настроек советую прочитать о синтаксисе [языка разметки yaml](http://ru.wikipedia.org/wiki/YAML).

### Изначальная настройка

Скопируйте файл [nbackup.yaml.example](https://github.com/druidvav/nekoBackup/blob/master/etc/nbackup.yaml.example)
в nbackup.yaml и отредактируйте его в соответствии с собственными требованиями, если хотите загружать бекапы в Amazon S3
— не забудьте заполнить соответствующую секцию в файле.

### Настройка объектов архивирования

В директории `etc/nbackup.d` расположены примеры файлов конфигурации для разных вариантов бекапирования. Скопируйте их
и донастройте под себя. В директории обрабатываются только файлы `.yaml`.

### Установка задач в крон

Для автоматического добавления задачи выполните:

```bash
/opt/nbackup2/sbin/nbackup install
```

## Использование

```bash
/opt/nbackup2/sbin/nbackup backup
```

* Запускает архивирование данных в директорию storage в соответствии с текущей датой.
* По окончании архивирования автоматически очищает просроченные файлы

```bash
/opt/nbackup2/sbin/nbackup upload
```

* Может выполняться параллельно архивированию: заливает готовые архивы в s3. Желательно запускать регулярно, чтобы
все файлы были гарантировано загружены

```bash
/opt/nbackup2/sbin/nbackup upload-cleanup
```

* Очищает просроченные файлы, загруженные в s3. Желательно запускать ежедневно в конце дня.