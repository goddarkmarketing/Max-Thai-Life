(function (global) {
  var BLOCK_CLASS = "pe-prose-p";
  var SURFACE_CLASS = "pe-prose-text";

  function esc(s) {
    var d = document.createElement("div");
    d.textContent = s || "";
    return d.innerHTML;
  }

  function createBlock(text) {
    var p = document.createElement("p");
    p.className = BLOCK_CLASS;
    if (text) p.textContent = text;
    else p.innerHTML = "<br>";
    return p;
  }

  function isSurface(el) {
    return el && el.classList && el.classList.contains(SURFACE_CLASS);
  }

  function htmlFromPlain(text) {
    var raw = (text || "").trim();
    if (!raw) return '<p class="' + BLOCK_CLASS + '"><br></p>';
    var parts = raw.split(/\n{2,}/);
    return parts
      .map(function (part) {
        return '<p class="' + BLOCK_CLASS + '">' + esc(part.replace(/\n/g, " ").trim()) + "</p>";
      })
      .join("");
  }

  function plainFromSurface(surface) {
    if (!surface) return "";
    var blocks = surface.querySelectorAll(":scope > ." + BLOCK_CLASS);
    if (!blocks.length) return (surface.textContent || "").trim();
    return Array.prototype.map
      .call(blocks, function (p) {
        return (p.textContent || "").trim();
      })
      .filter(Boolean)
      .join("\n\n");
  }

  function getBlockFromNode(node, surface) {
    while (node && node !== surface) {
      if (node.nodeType === 1) {
        if (node.classList && node.classList.contains(BLOCK_CLASS)) return node;
        if (node.tagName === "P") {
          node.classList.add(BLOCK_CLASS);
          return node;
        }
      }
      node = node.parentNode;
    }
    return null;
  }

  function placeCursor(block, atEnd) {
    if (!block) return;
    var surface = block.closest("." + SURFACE_CLASS);
    if (surface) surface.focus();
    var range = document.createRange();
    var sel = window.getSelection();
    if (atEnd) {
      range.selectNodeContents(block);
      range.collapse(false);
    } else {
      if (block.firstChild) {
        range.setStart(block.firstChild, 0);
      } else {
        range.selectNodeContents(block);
      }
      range.collapse(true);
    }
    sel.removeAllRanges();
    sel.addRange(range);
  }

  function normalizeSurface(surface) {
    if (!isSurface(surface)) return;

    var blocks = surface.querySelectorAll(":scope > ." + BLOCK_CLASS + ", :scope > p");
    if (!blocks.length) {
      var legacy = (surface.textContent || "").trim();
      surface.innerHTML = legacy ? htmlFromPlain(legacy) : '<p class="' + BLOCK_CLASS + '"><br></p>';
      return;
    }

    Array.prototype.forEach.call(blocks, function (p) {
      p.classList.add(BLOCK_CLASS);
    });

    var node = surface.firstChild;
    while (node) {
      var next = node.nextSibling;
      if (node.nodeType === 3 && node.textContent) {
        var last = surface.querySelector(":scope > ." + BLOCK_CLASS + ":last-of-type");
        var target = last || createBlock("");
        if (!last) surface.appendChild(target);
        target.appendChild(node);
      } else if (node.nodeType === 1 && !node.classList.contains(BLOCK_CLASS)) {
        if (node.tagName === "FIGURE" || node.tagName === "UL" || node.tagName === "OL" || node.tagName === "H3") {
          node = next;
          continue;
        }
        var replacement = createBlock((node.textContent || "").trim());
        surface.replaceChild(replacement, node);
      }
      node = next;
    }

    blocks = surface.querySelectorAll(":scope > ." + BLOCK_CLASS);
    if (!blocks.length) surface.appendChild(createBlock(""));

    Array.prototype.forEach.call(surface.querySelectorAll(":scope > ." + BLOCK_CLASS), function (p) {
      if (!(p.textContent || "").length && !p.querySelector("br")) {
        p.innerHTML = "<br>";
      }
    });
  }

  function handleEnter(e, surface) {
    e.preventDefault();
    var sel = window.getSelection();
    if (!sel.rangeCount) return;
    var range = sel.getRangeAt(0);
    var block = getBlockFromNode(range.startContainer, surface);
    if (!block) {
      block = createBlock("");
      surface.appendChild(block);
      placeCursor(block, true);
      return;
    }

    var pre = document.createRange();
    pre.selectNodeContents(block);
    pre.setEnd(range.startContainer, range.startOffset);
    var post = document.createRange();
    post.selectNodeContents(block);
    post.setStart(range.endContainer, range.endOffset);

    var beforeText = pre.toString();
    var afterText = post.toString();

    block.textContent = beforeText;
    if (!beforeText) block.innerHTML = "<br>";

    var newBlock = createBlock(afterText);
    block.parentNode.insertBefore(newBlock, block.nextSibling);
    placeCursor(newBlock, !afterText);
  }

  function handleBackspace(e, surface) {
    var sel = window.getSelection();
    if (!sel.rangeCount || !sel.isCollapsed) return;
    var range = sel.getRangeAt(0);
    var block = getBlockFromNode(range.startContainer, surface);
    if (!block) return;

    var atStart = false;
    if (range.startContainer === block) {
      atStart = range.startOffset === 0;
    } else if (range.startContainer.nodeType === 3) {
      var pre = document.createRange();
      pre.selectNodeContents(block);
      pre.setEnd(range.startContainer, range.startOffset);
      atStart = pre.toString().length === 0;
    }

    if (!atStart) return;
    var prev = block.previousElementSibling;
    if (!prev || !prev.classList.contains(BLOCK_CLASS)) return;
    if ((block.textContent || "").length) return;

    e.preventDefault();
    var prevText = prev.textContent || "";
    block.remove();
    prev.textContent = prevText;
    placeCursor(prev, true);
  }

  function handlePaste(e, surface) {
    e.preventDefault();
    var text = (e.clipboardData || window.clipboardData).getData("text/plain");
    if (text == null) return;

    var sel = window.getSelection();
    if (!sel.rangeCount) return;
    var range = sel.getRangeAt(0);
    var block = getBlockFromNode(range.startContainer, surface);
    if (!block) {
      block = createBlock("");
      surface.appendChild(block);
    }

    var lines = text.replace(/\r/g, "").split("\n");
    if (lines.length === 1) {
      range.deleteContents();
      range.insertNode(document.createTextNode(lines[0]));
      range.collapse(false);
      sel.removeAllRanges();
      sel.addRange(range);
      normalizeSurface(surface);
      return;
    }

    var pre = document.createRange();
    pre.selectNodeContents(block);
    pre.setEnd(range.startContainer, range.startOffset);
    var post = document.createRange();
    post.selectNodeContents(block);
    post.setStart(range.endContainer, range.endOffset);

    var beforeText = pre.toString();
    var afterText = post.toString();

    block.textContent = beforeText + lines[0];
    var ref = block;
    var i;
    for (i = 1; i < lines.length; i++) {
      var nb = createBlock(lines[i]);
      ref.parentNode.insertBefore(nb, ref.nextSibling);
      ref = nb;
    }
    if (afterText) ref.textContent = (ref.textContent || "") + afterText;

    placeCursor(ref, true);
    normalizeSurface(surface);
  }

  function prepareForEdit(surface, focusEnd) {
    normalizeSurface(surface);
    if (focusEnd) {
      var blocks = surface.querySelectorAll(":scope > ." + BLOCK_CLASS);
      var last = blocks[blocks.length - 1];
      if (last) placeCursor(last, true);
    }
  }

  function init(container) {
    if (!container || container.getAttribute("data-prose-block-bound")) return;
    container.setAttribute("data-prose-block-bound", "1");

    container.addEventListener(
      "keydown",
      function (e) {
        var surface = e.target.closest("." + SURFACE_CLASS);
        if (!surface || surface.getAttribute("contenteditable") !== "true") return;
        if (e.key === "Enter" && !e.shiftKey) handleEnter(e, surface);
        else if (e.key === "Backspace") handleBackspace(e, surface);
      },
      true
    );

    container.addEventListener(
      "beforeinput",
      function (e) {
        var surface = e.target.closest("." + SURFACE_CLASS);
        if (!surface || surface.getAttribute("contenteditable") !== "true") return;
        if (e.inputType === "insertFromPaste" || e.inputType === "insertFromDrop") {
          handlePaste(e, surface);
        }
      },
      true
    );

    container.addEventListener(
      "input",
      function (e) {
        var surface = e.target.closest("." + SURFACE_CLASS);
        if (!surface || surface.getAttribute("contenteditable") !== "true") return;
        window.requestAnimationFrame(function () {
          normalizeSurface(surface);
        });
      },
      true
    );
  }

  global.ProseBlockEditor = {
    init: init,
    htmlFromPlain: htmlFromPlain,
    plainFromSurface: plainFromSurface,
    normalize: normalizeSurface,
    prepareForEdit: prepareForEdit,
    getBlockFromNode: getBlockFromNode,
    placeCursor: placeCursor,
  };
})(window);
