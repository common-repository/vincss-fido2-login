<?php

namespace Safe;

use Safe\Exceptions\ComException;

/**
 * Instructs COM to sink events generated by
 * comobject into the PHP object
 * sinkobject.
 *
 * Be careful how you use this feature; if you are doing something similar
 * to the example below, then it doesn't really make sense to run it in a
 * web server context.
 *
 * @param object $comobject
 * @param object $sinkobject sinkobject should be an instance of a class with
 * methods named after those of the desired dispinterface; you may use
 * com_print_typeinfo to help generate a template class
 * for this purpose.
 * @param mixed $sinkinterface PHP will attempt to use the default dispinterface type specified by
 * the typelibrary associated with comobject, but
 * you may override this choice by setting
 * sinkinterface to the name of the dispinterface
 * that you want to use.
 * @throws ComException
 *
 */
function com_event_sink(object $comobject, object $sinkobject, $sinkinterface = null): void
{
    error_clear_last();
    if ($sinkinterface !== null) {
        $result = \com_event_sink($comobject, $sinkobject, $sinkinterface);
    } else {
        $result = \com_event_sink($comobject, $sinkobject);
    }
    if ($result === false) {
        throw ComException::createFromPhpError();
    }
}


/**
 * Loads a type-library and registers its constants in the engine, as though
 * they were defined using define.
 *
 * Note that it is much more efficient to use the  configuration setting to pre-load and
 * register the constants, although not so flexible.
 *
 * If you have turned on , then
 * PHP will attempt to automatically register the constants associated with a
 * COM object when you instantiate it.  This depends on the interfaces
 * provided by the COM object itself, and may not always be possible.
 *
 * @param string $typelib_name typelib_name can be one of the following:
 *
 *
 *
 * The filename of a .tlb file or the executable module
 * that contains the type library.
 *
 *
 *
 *
 * The type library GUID, followed by its version number, for example
 * {00000200-0000-0010-8000-00AA006D2EA4},2,0.
 *
 *
 *
 *
 * The type library name, e.g. Microsoft OLE DB ActiveX Data
 * Objects 1.0 Library.
 *
 *
 *
 * PHP will attempt to resolve the type library in this order, as the
 * process gets more and more expensive as you progress down the list;
 * searching for the type library by name is handled by physically
 * enumerating the registry until we find a match.
 *
 * The filename of a .tlb file or the executable module
 * that contains the type library.
 *
 * The type library GUID, followed by its version number, for example
 * {00000200-0000-0010-8000-00AA006D2EA4},2,0.
 *
 * The type library name, e.g. Microsoft OLE DB ActiveX Data
 * Objects 1.0 Library.
 * @param bool $case_sensitive The case_sensitive behaves inversely to
 * the parameter $case_insensitive in the define
 * function.
 * @throws ComException
 *
 */
function com_load_typelib(string $typelib_name, bool $case_sensitive = true): void
{
    error_clear_last();
    $result = \com_load_typelib($typelib_name, $case_sensitive);
    if ($result === false) {
        throw ComException::createFromPhpError();
    }
}


/**
 * The purpose of this function is to help generate a skeleton class for use
 * as an event sink.  You may also use it to generate a dump of any COM
 * object, provided that it supports enough of the introspection interfaces,
 * and that you know the name of the interface you want to display.
 *
 * @param object $comobject comobject should be either an instance of a COM
 * object, or be the name of a typelibrary (which will be resolved according
 * to the rules set out in com_load_typelib).
 * @param string $dispinterface The name of an IDispatch descendant interface that you want to display.
 * @param bool $wantsink If set to TRUE, the corresponding sink interface will be displayed
 * instead.
 * @throws ComException
 *
 */
function com_print_typeinfo(object $comobject, string $dispinterface = null, bool $wantsink = false): void
{
    error_clear_last();
    $result = \com_print_typeinfo($comobject, $dispinterface, $wantsink);
    if ($result === false) {
        throw ComException::createFromPhpError();
    }
}
