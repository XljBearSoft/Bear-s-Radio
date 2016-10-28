var Selected = function() {
    this.audio = document.getElementById('audio');
    this.lyricContainer = document.getElementById('lyricContainer');
    this.lyric = null;
};
Selected.prototype = {
    play: function() {
        var that = this;
        this.lyricContainer.style.top = '130px';
        this.lyric = null;
        this.audio.addEventListener('canplay', function() {
            that.getLyric("./api/?type=lrc");
        });
        that.getLyric("./api/?type=lrc");
        this.audio.addEventListener("timeupdate", function(e) {
            if (!that.lyric) return;
            for (var i = that.lyric.length - 1 ; i >= 0; i--) {
                if (this.currentTime > that.lyric[i][0] - 0.50) {
                    var line = document.getElementById('line-' + i),
                        prevLine = document.getElementById('line-' + (i > 0 ? i - 1 : i));
                    $(prevLine).removeClass('current-line');
                    line.className = 'current-line';
                    that.lyricContainer.style.top = document.body.clientHeight/3.2 - line.offsetTop + 'px';
                    break;
                };
            };
        });
    },
    getLyric: function(url) {
        var that = this,
            request = new XMLHttpRequest();
        request.open('GET', url, true);
        request.responseType = 'text';
        request.onload = function() {
            if(request.response.trim()==""){
                that.lyricContainer.textContent = '';
                return;
            }
            that.lyric = that.parseLyric(request.response);
            that.appendLyric(that.lyric);
        };
        request.onerror = request.onabort = function(e) {
            that.lyricContainer.textContent = '';
        }
        request.send();
    },
    parseLyric: function(text) {
        var lines = text.split('\n'),
            pattern = /\[\d{2}:\d{2}.\d{2}\]/g,
            result = [];
        var offset = this.getOffset(text);
        while (!pattern.test(lines[0])) {
            lines = lines.slice(1);
            if(lines.length==0)return;
        };
        while (lines[lines.length-1]=="") {
            lines.pop();
        };
        lines[lines.length - 1].length === 0 && lines.pop();
        lines.forEach(function(v, i, a) {
            var time = v.match(pattern),
                value = v.replace(pattern, '');
            time.forEach(function(v1, i1, a1) {
                var t = v1.slice(1, -1).split(':');
                result.push([parseInt(t[0], 10) * 60 + parseFloat(t[1]) + parseInt(offset) / 1000, value]);
            });
        });
        result.sort(function(a, b) {
            return a[0] - b[0];
        });
        return result;
    },
    appendLyric: function(lyric) {
        var that = this,
            lyricContainer = this.lyricContainer,
            fragment = document.createDocumentFragment();
        this.lyricContainer.innerHTML = '';
        lyric.forEach(function(v, i, a) {
            var line = document.createElement('p');
            line.id = 'line-' + i;
            line.textContent = v[1];
            fragment.appendChild(line);
        });
        lyricContainer.appendChild(fragment);
    },
    getOffset: function(text) {
        var offset = 0;
        try {
            var offsetPattern = /\[offset:\-?\+?\d+\]/g,
                offset_line = text.match(offsetPattern)[0],
                offset_str = offset_line.split(':')[1];
            offset = parseInt(offset_str);
        } catch (err) {
            offset = 0;
        }
        return offset;
    }
};